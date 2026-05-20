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
        private readonly string $apiSecret,
        private readonly int $timeout = 10,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($dispatcher);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $this->http
            ->timeout($this->timeout)
            ->withHeaders([
                'X-Faffless-Hub-Api-Key' => $this->apiKey,
                'X-Faffless-Hub-Api-Secret' => $this->apiSecret,
            ])
            ->post(rtrim($this->url, '/').'/api/v1/email', $this->payload($email, $envelope))
            ->throw();
    }

    public function __toString(): string
    {
        return 'faffless';
    }

    private function payload(Email $email, Envelope $envelope): array
    {
        return array_filter([
            'from' => $this->firstAddress($email->getFrom()),
            'to' => $this->addresses($envelope->getRecipients()),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'reply_to' => $this->addresses($email->getReplyTo()),
            'subject' => $email->getSubject() ?? '',
            'text' => $email->getTextBody(),
            'html' => $email->getHtmlBody(),
            'metadata' => [
                'message_id' => $email->getHeaders()->get('Message-ID')?->getBodyAsString(),
                'headers' => $this->headers($email),
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

    private function headers(Email $email): array
    {
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }
}
