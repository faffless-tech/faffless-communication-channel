<?php

namespace Faffless\CommunicationChannel;

class FafflessHubMessage
{
    public function __construct(
        private string $type,
        private array $payload
    ) {
    }

    public static function sms(string $to, string $text): self
    {
        return new self('sms', [
            'to' => $to,
            'text' => $text,
        ]);
    }

    public static function whatsapp(string $to, string $text): self
    {
        return new self('whatsapp', [
            'to' => $to,
            'text' => $text,
        ]);
    }

    public function group(string $groupId): self
    {
        $this->payload['group_id'] = $groupId;

        return $this;
    }

    public function description(string $description): self
    {
        $this->payload['description'] = $description;

        return $this;
    }

    public static function from(self|array $message): self
    {
        if ($message instanceof self) {
            return $message;
        }

        return new self($message['type'] ?? 'sms', $message);
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
