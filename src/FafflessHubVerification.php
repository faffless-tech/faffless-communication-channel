<?php

namespace Faffless\CommunicationChannel;

class FafflessHubVerification
{
    public function __construct(
        private FafflessHubClient $client = new FafflessHubClient
    ) {
    }

    public function start(string $channel, string $recipient, array $payload = []): array
    {
        return $this->client
            ->sendInteraction('/api/v1/verification', [
                'channel' => $channel,
                'recipient' => $recipient,
                ...$payload,
            ]);
    }

    public function check(string $verificationId, string $code): array
    {
        return $this->client
            ->post("/api/v1/verification/{$verificationId}/check", [
                'code' => $code,
            ])
            ->json();
    }

    public function next(string $verificationId, array $payload = []): array
    {
        return $this->client
            ->sendInteraction("/api/v1/verification/{$verificationId}/next", $payload);
    }

    public function retryVia(string $verificationId, string $channel, array $payload = []): array
    {
        return $this->next($verificationId, [
            'channel' => $channel,
            ...$payload,
        ]);
    }
}
