<?php

namespace App\Services\Royalties;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementMail;
use App\Models\Setting;
use App\Services\BrevoService;

/**
 * Sends each party its statement as PDF, through Brevo like the campaigns
 * (same sender). Subject and text take placeholders; a test run sends all
 * mails to one address instead - also for a draft, which is never sent to
 * the parties themselves.
 */
class RoyaltyStatementMailer
{
    public const TEMPLATE = 'royalties.mail_template';

    public const PLACEHOLDERS = [
        '{name}' => 'Name der Partei',
        '{zeitraum}' => 'Zeitraum der Nutzung',
        '{gutschrift}' => 'Gutschrift dieser Abrechnung',
        '{saldo}' => 'neuer Saldo',
        '{auszahlung}' => 'Satz zur Auszahlung (ab bzw. unter dem Mindestbetrag)',
        '{absender}' => 'Absender',
    ];

    private const DEFAULT_SUBJECT = 'Deine Abrechnung {zeitraum}';

    private const DEFAULT_BODY = "Hallo {name}\n\n"
        . "im Anhang findest Du Deine Abrechnung für Streaming & Downloads ({zeitraum}).\n"
        . "Deine Gutschrift beträgt CHF {gutschrift}, Dein Saldo neu CHF {saldo}.\n\n"
        . "{auszahlung}\n\n"
        . "Bei Fragen melde Dich gerne.\n\n"
        . "Herzliche Grüsse\n{absender}";

    public function __construct(private RoyaltyStatementDocument $document, private BrevoService $brevo)
    {
    }

    /** Subject and text as last used, else the default */
    public function template(): array
    {
        $saved = Setting::get(self::TEMPLATE, []);

        return [
            'subject' => $saved['subject'] ?? self::DEFAULT_SUBJECT,
            'body' => $saved['body'] ?? self::DEFAULT_BODY,
        ];
    }

    /** Where a party gets mail: its own address, for an organization else the one of its people */
    public static function address(?Organization $organization, ?Contact $contact): ?string
    {
        if ($organization) {
            return $organization->email ?: $organization->contacts()->whereNotNull('email')->where('email', '!=', '')->value('email');
        }

        return $contact?->email ?: null;
    }

    /**
     * @param array<string, string> $recipients party key => address
     * @return array{sent: int, failed: int, errors: list<string>}
     */
    public function send(RoyaltyStatement $statement, array $recipients, string $subject, string $body, ?string $testAddress = null): array
    {
        if (!$this->brevo->isConfigured()) {
            throw new RoyaltyStatementException('Der Mailversand (Brevo) ist nicht eingerichtet: BREVO_API_KEY fehlt in der .env.');
        }
        if (!$testAddress && !$statement->is_final) {
            throw new RoyaltyStatementException('Ein Entwurf geht nur als Probe hinaus. Zuerst die Abrechnung abschliessen.');
        }
        Setting::set(self::TEMPLATE, ['subject' => $subject, 'body' => $body]);

        ['result' => $result, 'parties' => $parties] = $this->document->prepare($statement->loadMissing('accounting'));
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $key => $address) {
            $party = $parties->firstWhere('key', $key);
            $to = $testAddress ?: trim((string) $address);
            if (!$party || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                $errors[] = ($party->name ?? $key) . ': keine gültige E-Mail-Adresse';
                continue;
            }

            $data = $this->document->data($statement, $party, $result);
            $values = $this->values($statement, $party, $data);
            $mailSubject = ($testAddress ? "[Probe für {$party->name}] " : '') . strtr($subject, $values);
            $html = nl2br(e(strtr($body, $values)));

            $response = $this->brevo->sendEmail(
                toEmail: $to,
                toName: $testAddress ? $to : $party->name,
                subject: $mailSubject,
                htmlContent: $html,
                attachments: [['name' => $this->document->fileName($statement, $party), 'content' => $this->document->pdf($statement, $party, $result, $data)->output()]],
            );

            RoyaltyStatementMail::create([
                'royalty_statement_id' => $statement->id,
                'organization_id' => $party->organization?->id,
                'contact_id' => $party->organization ? null : $party->contact?->id,
                'email' => $to,
                'status' => !$response['success'] ? RoyaltyStatementMail::FAILED : ($testAddress ? RoyaltyStatementMail::TEST : RoyaltyStatementMail::SENT),
                'message_id' => $response['message_id'] ?? null,
                'error' => $response['success'] ? null : mb_substr((string) ($response['error'] ?? 'Fehler'), 0, 255),
            ]);

            if ($response['success']) {
                $sent++;
            } else {
                $failed++;
                $errors[] = "{$party->name}: " . ($response['error'] ?? 'Fehler');
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'errors' => $errors];
    }

    private function values(RoyaltyStatement $statement, object $party, array $data): array
    {
        $chf = fn ($v) => number_format((float) $v, 2, '.', "'");
        $from = $statement->period_from;
        $to = $statement->period_to;
        $period = $from->format('m-d') === '01-01' && $to->format('m-d') === '12-31'
            ? ($from->year === $to->year ? (string) $to->year : "{$from->year}–{$to->year}")
            : $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y');

        return [
            '{name}' => $party->name,
            '{zeitraum}' => $period,
            '{gutschrift}' => $chf($party->credit),
            '{saldo}' => $chf($data['balanceAfter']),
            '{auszahlung}' => $data['balanceAfter'] >= $data['minimum']
                ? 'Den Saldo überweisen wir Dir in den nächsten Tagen.'
                : 'Beträge unter CHF ' . $chf($data['minimum']) . ' tragen wir auf die nächste Abrechnung vor – möchtest Du den Saldo trotzdem, melde Dich einfach.',
            '{absender}' => $data['senderLines'][0] ?? config('services.brevo.sender_name'),
        ];
    }
}
