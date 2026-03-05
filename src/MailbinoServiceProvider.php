<?php

namespace Mailbino\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Mailbino\Laravel\Transport\MailbinoTransport;

class MailbinoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mailbino.php', 'mailbino');

        $this->app->singleton(MailbinoClient::class, function ($app) {
            $config = $app['config']['mailbino'];

            return new MailbinoClient(
                apiToken: $config['api_token'] ?? '',
                baseUrl: $config['base_url'] ?? 'https://mailbino.com/api',
                testRecipient: $config['test_recipient'] ?? null,
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/mailbino.php' => config_path('mailbino.php'),
        ], 'mailbino-config');

        // Register the "mailbino" mail transport
        Mail::extend('mailbino', function () {
            return new MailbinoTransport(
                $this->app->make(MailbinoClient::class),
            );
        });
    }
}
