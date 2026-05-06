<?php

namespace Faffless\CommunicationChannel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FafflessHubClient
{
    public function post(string $path, array $payload = []): Response
    {
        return Http::timeout(config('faffless-hub.timeout'))
            ->withHeaders([
                'X-Faffless-Hub-App-Id' => config('faffless-hub.app_id'),
                'X-Faffless-Hub-Secret' => config('faffless-hub.secret'),
            ])
            ->post($this->url($path), $this->payload($payload))
            ->throw();
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
