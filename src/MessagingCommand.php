<?php

namespace Rpos\MessagingChannel;

class MessagingCommand
{
    public function __construct(
        private MessagingHubClient $client = new MessagingHubClient
    ) {
    }

    public static function tracker(?int $deviceId, string $type, array $attributes = []): MessagingMessage
    {
        return new MessagingMessage('tracker_command', array_filter([
            'device_id' => $deviceId,
            'type' => $type,
            'attributes' => $attributes,
        ], fn ($value) => $value !== null));
    }

    public function sendTracker(?int $deviceId, string $type, array $attributes = [], array $payload = []): array
    {
        return $this->client
            ->post('/api/v1/tracker/command', array_filter([
                'device_id' => $deviceId,
                'type' => $type,
                'attributes' => $attributes,
                ...$payload,
            ], fn ($value) => $value !== null))
            ->json();
    }
}
