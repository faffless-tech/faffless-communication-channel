<?php

namespace Faffless\CommunicationChannel;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class FafflessHubClient
{
    public function get(string $path): Response
    {
        return $this->request()
            ->get($this->url($path))
            ->throw();
    }

    public function post(string $path, array $payload = []): Response
    {
        return $this->request()
            ->post($this->url($path), $this->payload($payload))
            ->throw();
    }

    public function sendInteraction(string $path, array $payload = []): array
    {
        $response = $this->post($path, $payload)->json();

        if (! is_array($response)) {
            return [];
        }

        return $response;
    }

    public function interaction(int|string $interactionId): array
    {
        return $this->get("/api/v1/interactions/{$interactionId}")->json() ?? [];
    }

    public function attempts(int|string $interactionId): array
    {
        return $this->get("/api/v1/interactions/{$interactionId}/attempts")->json('attempts') ?? [];
    }

    public function events(int|string $interactionId): array
    {
        return $this->get("/api/v1/interactions/{$interactionId}/events")->json('events') ?? [];
    }

    public function artifacts(int|string $interactionId): array
    {
        return $this->get("/api/v1/interactions/{$interactionId}/artifacts")->json('artifacts') ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::timeout(config('faffless-hub.timeout'))
            ->withHeaders([
                'X-Faffless-Hub-App-Id' => config('faffless-hub.app_id'),
                'X-Faffless-Hub-Secret' => config('faffless-hub.secret'),
            ]);
    }

    private function url(string $path): string
    {
        return rtrim(config('faffless-hub.url'), '/').'/'.ltrim($path, '/');
    }

    private function payload(array $payload): array
    {
        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }
}
