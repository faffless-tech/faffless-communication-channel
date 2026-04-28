<?php

namespace Rpos\MessagingChannel;

class MessagingMessage
{
    public function __construct(
        private string $type,
        private array $payload
    ) {
    }

    public static function email(string $to, string $subject, ?string $text = null, ?string $html = null): self
    {
        return new self('email', array_filter([
            'to' => $to,
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
        ], fn ($value) => $value !== null));
    }

    public static function verification(string $channel, string $recipient): self
    {
        return new self('verification', [
            'channel' => $channel,
            'recipient' => $recipient,
        ]);
    }

    public static function from(self|array $message): self
    {
        if ($message instanceof self) {
            return $message;
        }

        return new self($message['type'] ?? 'email', $message);
    }

    public function purpose(string $purpose): self
    {
        $this->payload['purpose'] = $purpose;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->payload['metadata'] = $metadata;

        return $this;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
