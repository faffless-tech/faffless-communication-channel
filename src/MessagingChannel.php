<?php

namespace Rpos\MessagingChannel;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class MessagingChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMessagingService')) {
            return;
        }

        $message = MessagingMessage::from($notification->toMessagingService($notifiable));

        Http::timeout(config('messaging-channel.timeout'))
            ->withHeaders([
                'X-Messaging-App-Id' => config('messaging-channel.app_id'),
                'X-Messaging-Secret' => config('messaging-channel.secret'),
            ])
            ->post($this->endpoint($message), $this->payload($message))
            ->throw();
    }

    private function endpoint(MessagingMessage $message): string
    {
        $path = match ($message->type()) {
            'email' => '/api/v1/email',
            'sms' => '/api/v1/sms',
            'whatsapp' => '/api/v1/whatsapp',
            'tracker_command' => '/api/v1/tracker/commands',
            'verification' => '/api/v1/verifications',
            default => throw new UnsupportedMessageTypeException($message->type()),
        };

        return rtrim(config('messaging-channel.url'), '/').$path;
    }

    private function payload(MessagingMessage $message): array
    {
        return array_filter([
            'brand_key' => config('messaging-channel.brand_key'),
            'brand_name' => config('messaging-channel.brand_name'),
            'brand_region' => config('messaging-channel.brand_region'),
            ...$message->payload(),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
