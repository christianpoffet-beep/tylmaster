<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Services\BrevoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public Campaign $campaign
    ) {}

    public function handle(BrevoService $brevo): void
    {
        $campaign = $this->campaign;

        if ($campaign->status !== 'sending') {
            Log::warning("Campaign #{$campaign->id} not in 'sending' status, skipping.");
            return;
        }

        if (!$brevo->isConfigured()) {
            $campaign->update(['status' => 'draft']);
            Log::error("Brevo API key not configured. Campaign #{$campaign->id} reset to draft.");
            return;
        }

        $addressCircle = $campaign->addressCircle;
        if (!$addressCircle) {
            $campaign->update(['status' => 'draft']);
            Log::error("No address circle linked. Campaign #{$campaign->id} reset to draft.");
            return;
        }

        // Collect all recipients from the address circle
        $recipients = $this->collectRecipients($addressCircle);

        if (empty($recipients)) {
            $campaign->update(['status' => 'draft']);
            Log::warning("No recipients found. Campaign #{$campaign->id} reset to draft.");
            return;
        }

        // Remember addresses already delivered, so a retry never mails them twice
        $alreadySent = $campaign->sends()
            ->where('status', 'sent')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower($email))
            ->flip()
            ->all();

        $sentCount = 0;
        $failCount = 0;
        $skipCount = 0;

        foreach ($recipients as $recipient) {
            // Skip if no email
            if (empty($recipient['email'])) {
                continue;
            }

            // Skip anyone who already received this campaign
            if (isset($alreadySent[mb_strtolower($recipient['email'])])) {
                $skipCount++;
                continue;
            }

            // Create send record
            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'recipient_type' => $recipient['type'],
                'recipient_id' => $recipient['id'],
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'status' => 'pending',
            ]);

            // Replace placeholders
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

            // Small delay to respect API rate limits
            usleep(200000); // 200ms
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $campaign->sends()->where('status', 'sent')->count(),
        ]);

        Log::info("Campaign #{$campaign->id} sent. {$sentCount} delivered, {$failCount} failed, {$skipCount} skipped (already sent).");
    }

    protected function collectRecipients($addressCircle): array
    {
        $recipients = [];
        $seenEmails = [];

        // Contact members
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

        // Organization members
        foreach ($addressCircle->organizationMembers as $org) {
            $email = $org->pivot->email_override ?: $org->email;
            if ($email && !isset($seenEmails[$email])) {
                $seenEmails[$email] = true;
                $recipients[] = [
                    'type' => 'organization',
                    'id' => $org->id,
                    'email' => $email,
                    'name' => $org->primary_name,
                    'first_name' => '',
                    'last_name' => '',
                    'organization' => $org->primary_name,
                ];
            }
        }

        return $recipients;
    }

    public function failed(\Throwable $exception): void
    {
        $this->campaign->update(['status' => 'draft']);
        Log::error("SendCampaignJob failed for campaign #{$this->campaign->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
