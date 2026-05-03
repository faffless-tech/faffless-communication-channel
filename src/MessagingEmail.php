<?php

namespace Rpos\MessagingChannel;

class MessagingEmail
{
    public function __construct(
        private MessagingHubClient $client = new MessagingHubClient
    ) {
    }

    public static function message(string $to, string $subject, ?string $text = null, ?string $html = null): MessagingMessage
    {
        return new MessagingMessage('email', array_filter([
            'to' => $to,
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
        ], fn ($value) => $value !== null));
    }

    public function send(string $to, string $subject, ?string $text = null, ?string $html = null, array $payload = []): array
    {
        return $this->client
            ->post('/api/v1/email', [
                'to' => $to,
                'subject' => $subject,
                'text' => $text,
                'html' => $html,
                ...$payload,
            ])
            ->json();
    }
}
