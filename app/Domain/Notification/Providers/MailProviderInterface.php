<?php

namespace App\Domain\Notification\Providers;

interface MailProviderInterface
{
    /**
     * Send an email using the provider's API.
     *
     * @param  array<int, array<string, string>>  $attachments  Each entry:
     *                                                          filename, content (base64), content_type, and optionally
     *                                                          content_id - when present the part is an inline CID image the
     *                                                          HTML body references as src="cid:{content_id}".
     * @return array Response structure (e.g. ['id' => '...', 'status' => 'success'])
     */
    public function send(string $to, string $subject, string $htmlBody, array $headers = [], array $attachments = []): array;
}
