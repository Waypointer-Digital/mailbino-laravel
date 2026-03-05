<?php

namespace Mailbino\Laravel\Transport;

use Mailbino\Laravel\MailbinoClient;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class MailbinoTransport extends AbstractTransport
{
    public function __construct(
        protected MailbinoClient $client,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $payload = $this->buildPayload($email);

        $result = $this->client->send($payload);

        // Store the Mailbino message ID in a header for reference
        if (isset($result['message_id'])) {
            $email->getHeaders()->addTextHeader('X-Mailbino-Message-ID', $result['message_id']);
        }
    }

    protected function buildPayload(Email $email): array
    {
        $from = $email->getFrom()[0] ?? null;

        $payload = [
            'from' => $from?->getAddress(),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($from?->getName()) {
            $payload['from_name'] = $from->getName();
        }

        // Primary recipient
        $to = $email->getTo();
        if (count($to) === 1) {
            $payload['to'] = $this->formatAddress($to[0]);
        } elseif (count($to) > 1) {
            $payload['to'] = $this->formatAddress($to[0]);
            // Additional "to" recipients go to CC (Mailbino has single primary recipient)
            $extraTo = array_slice($to, 1);
            $existingCc = $email->getCc();
            $email = $email->cc(...array_merge($existingCc, $extraTo));
        }

        // CC
        $cc = $email->getCc();
        if (! empty($cc)) {
            $payload['cc'] = array_map(fn (Address $a) => $this->formatAddress($a), $cc);
        }

        // BCC
        $bcc = $email->getBcc();
        if (! empty($bcc)) {
            $payload['bcc'] = array_map(fn (Address $a) => $this->formatAddress($a), $bcc);
        }

        // Reply-To
        $replyTo = $email->getReplyTo();
        if (! empty($replyTo)) {
            $payload['reply_to'] = array_map(fn (Address $a) => $this->formatAddress($a), $replyTo);
        }

        // Body
        if ($email->getHtmlBody()) {
            $payload['html'] = $email->getHtmlBody();
        }
        if ($email->getTextBody()) {
            $payload['text'] = $email->getTextBody();
        }

        // Extract Mailbino-specific headers
        $headers = $email->getHeaders();

        $mailbinoHeaders = [
            'X-Mailbino-Tags' => 'tags',
            'X-Mailbino-Scope-Type' => 'scope_type',
            'X-Mailbino-Scope-Id' => 'scope_id',
            'X-Mailbino-Test-Recipient' => 'test_recipient',
            'X-Mailbino-External-Id' => 'external_message_id',
        ];

        foreach ($mailbinoHeaders as $header => $key) {
            if ($headers->has($header)) {
                $value = $headers->get($header)->getBodyAsString();
                if ($key === 'tags') {
                    $payload[$key] = array_map('trim', explode(',', $value));
                } else {
                    $payload[$key] = $value;
                }
                $headers->remove($header);
            }
        }

        // Metadata from header (JSON-encoded)
        if ($headers->has('X-Mailbino-Metadata')) {
            $metadata = json_decode($headers->get('X-Mailbino-Metadata')->getBodyAsString(), true);
            if (is_array($metadata)) {
                $payload['metadata'] = $metadata;
            }
            $headers->remove('X-Mailbino-Metadata');
        }

        // Pass through remaining custom headers
        $customHeaders = [];
        $reserved = ['from', 'to', 'cc', 'bcc', 'subject', 'message-id', 'mime-version', 'content-type', 'date'];
        foreach ($headers->all() as $header) {
            $name = strtolower($header->getName());
            if (! in_array($name, $reserved) && ! str_starts_with($name, 'x-mailbino-')) {
                $customHeaders[$header->getName()] = $header->getBodyAsString();
            }
        }
        if (! empty($customHeaders)) {
            $payload['headers'] = $customHeaders;
        }

        return $payload;
    }

    protected function formatAddress(Address $address): array
    {
        $result = ['email' => $address->getAddress()];
        if ($address->getName()) {
            $result['name'] = $address->getName();
        }

        return $result;
    }

    public function __toString(): string
    {
        return 'mailbino';
    }
}
