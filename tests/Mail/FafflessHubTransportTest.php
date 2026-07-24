<?php

namespace Faffless\CommunicationChannel\Tests\Mail;

use Faffless\CommunicationChannel\Mail\FafflessHubTransport;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FafflessHubTransportTest extends TestCase
{
    private Factory $http;

    private FafflessHubTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Factory;
        $this->http->fake([
            'https://hub.example.test/api/v1/application/email' => $this->http->response([
                'data' => [
                    'interaction_uuid' => '019f-example',
                    'status' => 'queued',
                ],
            ], 202),
        ]);
        $this->transport = new FafflessHubTransport(
            http: $this->http,
            url: 'https://hub.example.test',
            apiKey: 'app_test_secret',
        );
    }

    public function test_it_maps_addresses_and_bodies_without_duplicating_cc_and_bcc_as_to_recipients(): void
    {
        $email = (new Email)
            ->from(new Address('sender@example.test', 'Sender'))
            ->to(new Address('customer@example.test', 'Customer'))
            ->cc('manager@example.test')
            ->bcc('audit@example.test')
            ->replyTo('support@example.test')
            ->subject('Account verification')
            ->text('Your text verification message.')
            ->html('<p>Your HTML verification message.</p>');

        $this->transport->send($email);

        $this->http->assertSent(function (Request $request): bool {
            return $request->url() === 'https://hub.example.test/api/v1/application/email'
                && $request->hasHeader('Authorization', 'Bearer app_test_secret')
                && $request['from'] === ['email' => 'sender@example.test', 'name' => 'Sender']
                && $request['to'] === [['email' => 'customer@example.test', 'name' => 'Customer']]
                && $request['cc'] === [['email' => 'manager@example.test']]
                && $request['bcc'] === [['email' => 'audit@example.test']]
                && $request['reply_to'] === [['email' => 'support@example.test']]
                && $request['subject'] === 'Account verification'
                && $request['text'] === 'Your text verification message.'
                && $request['html'] === '<p>Your HTML verification message.</p>';
        });
    }

    public function test_it_uses_a_stable_hashed_explicit_idempotency_key(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader('X-Faffless-Idempotency-Key', 'registration:019f-example');

        $this->transport->send($email);

        $expected = 'mail:'.hash('sha256', 'registration:019f-example');

        $this->http->assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', $expected));
    }

    public function test_it_falls_back_to_a_stable_hashed_message_id_for_idempotency(): void
    {
        $email = $this->email();
        $email->getHeaders()->addIdHeader('Message-ID', 'stable-message@example.test');

        $this->transport->send($email);

        $expected = 'mail:'.hash('sha256', 'stable-message@example.test');

        $this->http->assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', $expected));
    }

    public function test_it_maps_purpose_and_sensitive_control_headers_without_leaking_headers(): void
    {
        $email = $this->email();
        $email->getHeaders()->addTextHeader('X-Faffless-Purpose', 'email_verification');
        $email->getHeaders()->addTextHeader('X-Faffless-Sensitive', 'true');
        $email->getHeaders()->addTextHeader('X-Internal-Secret', 'must-not-leak');

        $this->transport->send($email);

        $this->http->assertSent(function (Request $request): bool {
            $payload = $request->data();
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

            return $payload['purpose'] === 'email_verification'
                && $payload['metadata']['sensitive'] === true
                && ! array_key_exists('headers', $payload['metadata'])
                && ! str_contains($encoded, 'X-Faffless-Purpose')
                && ! str_contains($encoded, 'X-Faffless-Sensitive')
                && ! str_contains($encoded, 'X-Faffless-Idempotency-Key')
                && ! str_contains($encoded, 'X-Internal-Secret')
                && ! str_contains($encoded, 'must-not-leak');
        });
    }

    public function test_it_does_not_send_an_idempotency_header_when_no_stable_source_exists(): void
    {
        $this->transport->send($this->email());

        $this->http->assertSent(fn (Request $request): bool => ! $request->hasHeader('Idempotency-Key'));
    }

    public function test_it_uses_custom_envelope_recipients_when_the_message_has_no_to_header(): void
    {
        $email = (new Email)
            ->from('sender@example.test')
            ->bcc('audit@example.test')
            ->subject('Envelope test')
            ->text('Envelope body');
        $envelope = new Envelope(
            new Address('sender@example.test'),
            [new Address('envelope@example.test')],
        );

        $this->transport->send($email, $envelope);

        $this->http->assertSent(
            fn (Request $request): bool => $request['to'] === [['email' => 'envelope@example.test']]
        );
    }

    private function email(): Email
    {
        return (new Email)
            ->from('sender@example.test')
            ->to('customer@example.test')
            ->subject('Test')
            ->text('Test body');
    }
}
