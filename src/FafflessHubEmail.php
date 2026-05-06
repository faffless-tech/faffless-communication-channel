<?php

namespace Faffless\CommunicationChannel;

class FafflessHubEmail
{
    public function __construct(
        private FafflessHubClient $client = new FafflessHubClient
    ) {
    }

    public static function message(string $to, string $subject, ?string $text = null, ?string $html = null): FafflessHubMessage
    {
        return new FafflessHubMessage('email', array_filter([
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
