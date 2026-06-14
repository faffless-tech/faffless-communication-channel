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
            'email' => 'email',
            'sms' => 'sms',
            'whatsapp' => 'whatsapp',
            'tracker_command' => 'tracker/command',
            'verification' => 'verification',
            default => throw new UnsupportedFafflessHubMessageTypeException($message->type()),
        };

        return $path;
    }
}
