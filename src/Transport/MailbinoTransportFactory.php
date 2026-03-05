<?php

namespace Mailbino\Laravel\Transport;

use Mailbino\Laravel\MailbinoClient;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailbinoTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        protected MailbinoClient $client,
    ) {
        parent::__construct();
    }

    public function create(Dsn $dsn): TransportInterface
    {
        return new MailbinoTransport($this->client);
    }

    protected function getSupportedSchemes(): array
    {
        return ['mailbino'];
    }
}
