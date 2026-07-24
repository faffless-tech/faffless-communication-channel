<?php

namespace Faffless\CommunicationChannel\Mail;

use Illuminate\Http\Client\Factory as HttpFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FafflessHubTransport extends AbstractTransport
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly string $apiKey,
        private readonly int $timeout = 10,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($dispatcher);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();
        $idempotencyKey = $this->idempotencyKey($email);

        $this->http
            ->timeout($this->timeout)
            ->withToken($this->apiKey)
            ->withHeaders(array_filter([
                'Idempotency-Key' => $idempotencyKey,
            ]))
            ->post(rtrim($this->url, '/').'/api/v1/application/email', $this->payload($email, $envelope))
            ->throw();
    }

    public function __toString(): string
    {
        return 'faffless';
    }

    private function payload(Email $email, Envelope $envelope): array
    {
        $to = $email->getTo();

        return array_filter([
            'from' => $this->firstAddress($email->getFrom()),
            'to' => $this->addresses($to === [] ? $envelope->getRecipients() : $to),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'reply_to' => $this->addresses($email->getReplyTo()),
            'subject' => $email->getSubject() ?? '',
            'text' => $email->getTextBody(),
            'html' => $email->getHtmlBody(),
            'purpose' => $this->controlHeader($email, 'X-Faffless-Purpose'),
            'metadata' => [
                'message_id' => $email->getHeaders()->get('Message-ID')?->getBodyAsString(),
                'sensitive' => $this->sensitive($email),
            ],
        ], fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address) => array_filter([
            'email' => $address->getAddress(),
            'name' => $address->getName(),
        ]), $addresses);
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function firstAddress(array $addresses): ?array
    {
        return $addresses === [] ? null : $this->addresses([$addresses[0]])[0];
    }

    private function idempotencyKey(Email $email): ?string
    {
        $source = $this->controlHeader($email, 'X-Faffless-Idempotency-Key')
            ?? $email->getHeaders()->get('Message-ID')?->getBodyAsString();

        if (! $source) {
            return null;
        }

        return 'mail:'.hash('sha256', trim($source, " \t\n\r\0\x0B<>"));
    }

    private function sensitive(Email $email): ?bool
    {
        $value = $this->controlHeader($email, 'X-Faffless-Sensitive');

        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    private function controlHeader(Email $email, string $name): ?string
    {
        $value = $email->getHeaders()->get($name)?->getBodyAsString();

        return filled($value) ? trim($value) : null;
    }
}
