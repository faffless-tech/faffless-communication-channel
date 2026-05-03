<?php

namespace Rpos\MessagingChannel;

use Illuminate\Notifications\Notification;

class MessagingChannel
{
    public function __construct(
        private MessagingHubClient $client = new MessagingHubClient
    ) {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMessagingService')) {
            return;
        }

        $message = MessagingMessage::from($notification->toMessagingService($notifiable));

        $this->client->post($this->endpoint($message), $message->payload());
    }

    private function endpoint(MessagingMessage $message): string
    {
        $path = match ($message->type()) {
            'email' => '/api/v1/email',
            'sms' => '/api/v1/sms',
            'whatsapp' => '/api/v1/whatsapp',
            'tracker_command' => '/api/v1/tracker/command',
            'verification' => '/api/v1/verification',
            default => throw new UnsupportedMessageTypeException($message->type()),
        };

        return $path;
    }
}
