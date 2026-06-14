<?php

namespace Faffless\CommunicationChannel;

class FafflessHubCommand
{
    public function __construct(
        private FafflessHubClient $client = new FafflessHubClient
    ) {
    }

    public static function tracker(?string $deviceId, string $type, array $attributes = []): FafflessHubMessage
    {
        return new FafflessHubMessage('tracker_command', array_filter([
            'device_id' => $deviceId,
            'type' => $type,
            'attributes' => $attributes,
        ], fn ($value) => $value !== null));
    }

    public function sendTracker(?string $deviceId, string $type, array $attributes = [], array $payload = []): array
    {
        return $this->client
            ->sendInteraction('tracker/command', array_filter([
                'device_id' => $deviceId,
                'type' => $type,
                'attributes' => $attributes,
                ...$payload,
            ], fn ($value) => $value !== null));
    }
}
