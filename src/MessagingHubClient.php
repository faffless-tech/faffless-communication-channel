<?php

namespace Rpos\MessagingChannel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MessagingHubClient
{
    public function post(string $path, array $payload = []): Response
    {
        return Http::timeout(config('messaging-channel.timeout'))
            ->withHeaders([
                'X-Messaging-App-Id' => config('messaging-channel.app_id'),
                'X-Messaging-Secret' => config('messaging-channel.secret'),
            ])
            ->post($this->url($path), $this->payload($payload))
            ->throw();
    }

    private function url(string $path): string
    {
        return rtrim(config('messaging-channel.url'), '/').'/'.ltrim($path, '/');
    }

    private function payload(array $payload): array
    {
        return array_filter([
            'brand_key' => config('messaging-channel.brand_key'),
            'brand_name' => config('messaging-channel.brand_name'),
            'brand_region' => config('messaging-channel.brand_region'),
            ...$payload,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
