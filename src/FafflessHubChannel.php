<?php

namespace Faffless\CommunicationChannel;

use Illuminate\Notifications\Notification;

class FafflessHubChannel
{
    public function __construct(
        private FafflessHubClient $client = new FafflessHubClient
    ) {
    }

    public function send(object $notifiable, Notification $notification): ?array
    {
        if (! method_exists($notification, 'toCommunicationHub')) {
            return null;
        }

        $message = FafflessHubMessage::from($notification->toCommunicationHub($notifiable));

        return $this->client->sendInteraction($this->endpoint($message), $message->payload());
    }

    private function endpoint(FafflessHubMessage $message): string
    {
        $path = match ($message->type()) {
            'email' => '/api/v1/email',
            'sms' => '/api/v1/sms',
            'whatsapp' => '/api/v1/whatsapp',
            'tracker_command' => '/api/v1/tracker/command',
            'verification' => '/api/v1/verification',
            default => throw new UnsupportedFafflessHubMessageTypeException($message->type()),
        };

        return $path;
    }
}
