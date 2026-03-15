<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.brevo.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Send a transactional email via Brevo API.
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        ?string $senderName = null,
        ?string $senderEmail = null,
        ?string $replyTo = null,
    ): array {
        $senderName = $senderName ?: config('services.brevo.sender_name');
        $senderEmail = $senderEmail ?: config('services.brevo.sender_email');

        $payload = [
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail,
            ],
            'to' => [
                ['email' => $toEmail, 'name' => $toName],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        if ($replyTo) {
            $payload['replyTo'] = ['email' => $replyTo];
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/smtp/email", $payload);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('messageId'),
            ];
        }

        Log::error('Brevo API error', [
            'status' => $response->status(),
            'body' => $response->json(),
            'to' => $toEmail,
        ]);

        return [
            'success' => false,
            'error' => $response->json('message', 'Unbekannter Fehler'),
            'code' => $response->status(),
        ];
    }

    /**
     * Replace placeholders in content with recipient data.
     */
    public function replacePlaceholders(string $content, array $data): string
    {
        $replacements = [
            '{name}' => $data['name'] ?? '',
            '{email}' => $data['email'] ?? '',
            '{organization}' => $data['organization'] ?? '',
            '{first_name}' => $data['first_name'] ?? '',
            '{last_name}' => $data['last_name'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Get Brevo account info (for checking API key validity).
     */
    public function getAccount(): ?array
    {
        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
        ])->get("{$this->baseUrl}/account");

        return $response->successful() ? $response->json() : null;
    }
}
