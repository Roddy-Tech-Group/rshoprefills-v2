<?php

namespace App\Domain\Notification\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendProvider implements MailProviderInterface
{
    private readonly string $apiKey;

    private readonly string $fromAddress;

    private readonly string $fromName;

    public function __construct()
    {
        $this->apiKey = config('services.resend.key') ?? '';
        $this->fromAddress = config('services.resend.from_address') ?? 'noreply@rshoprefills.com';
        $this->fromName = config('services.resend.from_name') ?? 'RshopRefills';
    }

    public function send(string $to, string $subject, string $htmlBody, array $headers = []): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'testing' || app()->environment('testing')) {
            Log::info('Resend dry-run / sandbox mode. Email intercepted.', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return [
                'id' => 'mock_resend_id_'.uniqid(),
                'status' => 'success',
                'dry_run' => true,
            ];
        }

        $response = Http::withToken($this->apiKey)
            ->post('https://api.resend.com/emails', [
                'from' => "{$this->fromName} <{$this->fromAddress}>",
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlBody,
                'headers' => (object) $headers,
            ]);

        if ($response->failed()) {
            Log::error('Resend API call failed', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            throw new \RuntimeException('Resend email delivery failed: '.($response->json()['message'] ?? $response->body()));
        }

        return $response->json();
    }
}
