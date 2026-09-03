<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\CampaignTemplate;
use App\Models\AddressCircle;
use App\Services\BrevoService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['template', 'addressCircle']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['name', 'status', 'created_at', 'sent_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $campaigns = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $templates = CampaignTemplate::orderBy('sort_order')->get();
        $addressCircles = AddressCircle::orderBy('name')->get();

        return view('admin.campaigns.create', compact('templates', 'addressCircles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:email,letter',
            'language' => 'required|string|max:5',
            'campaign_template_id' => 'nullable|exists:campaign_templates,id',
            'address_circle_id' => 'nullable|exists:address_circles,id',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $campaign = Campaign::create($validated);

        return redirect()->route('admin.campaigns.edit', $campaign)->with('success', 'Kampagne erstellt.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['template', 'addressCircle', 'sends']);
        $sendStats = [
            'total' => $campaign->sends->count(),
            'sent' => $campaign->sends->where('status', 'sent')->count(),
            'failed' => $campaign->sends->where('status', 'failed')->count(),
            'pending' => $campaign->sends->where('status', 'pending')->count(),
        ];
        return view('admin.campaigns.show', compact('campaign', 'sendStats'));
    }

    public function edit(Campaign $campaign)
    {
        $templates = CampaignTemplate::orderBy('sort_order')->get();
        $addressCircles = AddressCircle::orderBy('name')->get();

        return view('admin.campaigns.edit', compact('campaign', 'templates', 'addressCircles'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:email,letter',
            'language' => 'required|string|max:5',
            'campaign_template_id' => 'nullable|exists:campaign_templates,id',
            'address_circle_id' => 'nullable|exists:address_circles,id',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.campaigns.edit', $campaign)->with('success', 'Kampagne aktualisiert.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('admin.campaigns.index')->with('success', 'Kampagne gelöscht.');
    }

    public function duplicate(Campaign $campaign)
    {
        $copy = $campaign->replicate();
        $copy->name = $campaign->name . ' (Kopie)';
        $copy->status = 'draft';
        $copy->sent_at = null;
        $copy->recipients_count = 0;
        $copy->save();

        return redirect()->route('admin.campaigns.edit', $copy)->with('success', 'Kampagne dupliziert.');
    }

    /**
     * Send a test email to a specific address.
     */
    public function testSend(Request $request, Campaign $campaign)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $brevo = app(BrevoService::class);

        if (!$brevo->isConfigured()) {
            return back()->with('error', 'Brevo API-Key ist nicht konfiguriert. Bitte in .env eintragen.');
        }

        $testData = [
            'name' => 'Test Empfänger',
            'first_name' => 'Test',
            'last_name' => 'Empfänger',
            'email' => $request->test_email,
            'organization' => 'Test Organisation',
        ];

        $subject = $brevo->replacePlaceholders($campaign->subject ?? '', $testData);
        $body = $brevo->replacePlaceholders($campaign->body ?? '', $testData);

        $result = $brevo->sendEmail(
            toEmail: $request->test_email,
            toName: 'Test Empfänger',
            subject: '[TEST] ' . $subject,
            htmlContent: $body,
            senderName: $campaign->sender_name,
            senderEmail: $campaign->sender_email,
            replyTo: $campaign->reply_to,
        );

        if ($result['success']) {
            return back()->with('success', 'Test-E-Mail an ' . $request->test_email . ' gesendet.');
        }

        return back()->with('error', 'Fehler beim Senden: ' . ($result['error'] ?? 'Unbekannt'));
    }

    /**
     * Start sending the campaign to all address circle members.
     */
    public function send(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'sending'])) {
            return back()->with('error', 'Nur Entwürfe können gesendet werden.');
        }

        if (!$campaign->address_circle_id) {
            return back()->with('error', 'Kein Adresskreis verknüpft. Bitte zuerst einen Adresskreis zuweisen.');
        }

        if (!$campaign->subject || !$campaign->body) {
            return back()->with('error', 'Betreff und Inhalt müssen ausgefüllt sein.');
        }

        $brevo = app(BrevoService::class);
        if (!$brevo->isConfigured()) {
            return back()->with('error', 'Brevo API-Key ist nicht konfiguriert. Bitte in .env eintragen.');
        }

        // Remember addresses already delivered, so a retry never mails them twice
        $alreadySent = $campaign->sends()
            ->where('status', 'sent')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower($email))
            ->flip()
            ->all();

        // Only discard failed/pending records from an earlier attempt, keep 'sent'
        $campaign->sends()->where('status', '!=', 'sent')->delete();
        $campaign->update(['status' => 'sending']);

        $addressCircle = $campaign->addressCircle;
        $recipients = $this->collectRecipients($addressCircle);

        if (empty($recipients)) {
            $campaign->update(['status' => 'draft']);
            return back()->with('error', 'Keine Empfänger im Adresskreis gefunden.');
        }

        set_time_limit(300);
        $sentCount = 0;
        $failCount = 0;
        $skipCount = 0;

        foreach ($recipients as $recipient) {
            if (empty($recipient['email'])) continue;

            if (isset($alreadySent[mb_strtolower($recipient['email'])])) {
                $skipCount++;
                continue;
            }

            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'recipient_type' => $recipient['type'],
                'recipient_id' => $recipient['id'],
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'status' => 'pending',
            ]);

            $subject = $brevo->replacePlaceholders($campaign->subject ?? '', $recipient);
            $body = $brevo->replacePlaceholders($campaign->body ?? '', $recipient);

            $result = $brevo->sendEmail(
                toEmail: $recipient['email'],
                toName: $recipient['name'],
                subject: $subject,
                htmlContent: $body,
                senderName: $campaign->sender_name,
                senderEmail: $campaign->sender_email,
                replyTo: $campaign->reply_to,
            );

            if ($result['success']) {
                $send->update([
                    'status' => 'sent',
                    'brevo_message_id' => $result['message_id'] ?? null,
                    'sent_at' => now(),
                ]);
                $sentCount++;
            } else {
                $send->update([
                    'status' => 'failed',
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                $failCount++;
            }

            usleep(200000); // 200ms rate limit
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $campaign->sends()->where('status', 'sent')->count(),
        ]);

        $message = "{$sentCount} E-Mails gesendet.";
        if ($skipCount > 0) {
            $message .= " {$skipCount} bereits zugestellt, übersprungen.";
        }
        if ($failCount > 0) {
            $message .= " {$failCount} fehlgeschlagen.";
        }

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', $message);
    }

    protected function collectRecipients($addressCircle): array
    {
        $recipients = [];
        $seenEmails = [];

        foreach ($addressCircle->contactMembers as $contact) {
            $email = $contact->pivot->email_override ?: $contact->email;
            if ($email && !isset($seenEmails[$email])) {
                $seenEmails[$email] = true;
                $recipients[] = [
                    'type' => 'contact',
                    'id' => $contact->id,
                    'email' => $email,
                    'name' => $contact->full_name,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'organization' => '',
                ];
            }
        }

        foreach ($addressCircle->organizationMembers as $org) {
            $email = $org->pivot->email_override ?: $org->email;
            if ($email && !isset($seenEmails[$email])) {
                $seenEmails[$email] = true;
                $recipients[] = [
                    'type' => 'organization',
                    'id' => $org->id,
                    'email' => $email,
                    'name' => $org->name,
                    'first_name' => '',
                    'last_name' => '',
                    'organization' => $org->name,
                ];
            }
        }

        return $recipients;
    }

    /**
     * Show send log for a campaign.
     */
    public function sendLog(Campaign $campaign)
    {
        $sends = $campaign->sends()->orderBy('created_at', 'desc')->paginate(50);
        return view('admin.campaigns.send-log', compact('campaign', 'sends'));
    }
}
