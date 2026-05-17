<?php

namespace App\Domain\Notification\Providers;

interface MailProviderInterface
{
    /**
     * Send an email using the provider's API.
     *
     * @return array Response structure (e.g. ['id' => '...', 'status' => 'success'])
     */
    public function send(string $to, string $subject, string $htmlBody, array $headers = []): array;
}
