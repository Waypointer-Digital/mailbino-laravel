<?php

namespace Mailbino\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Mailbino\Laravel\MailbinoClient;

/**
 * @method static array send(array $payload)
 * @method static array messages(array $filters = [])
 * @method static array message(string $messageId)
 * @method static array events(string $messageId)
 * @method static array inbound(string $id)
 *
 * @see \Mailbino\Laravel\MailbinoClient
 */
class Mailbino extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MailbinoClient::class;
    }
}
