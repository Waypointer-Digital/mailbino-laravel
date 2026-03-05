# Mailbino for Laravel

Laravel SDK for [Mailbino](https://mailbino.com) — transactional email that's easy to test.

Provides both a **Laravel mail driver** (use `Mail::send()` as usual) and a **direct API client** for Mailbino-specific features like tags, metadata, scoping, and test mode.

## Installation

```bash
composer require mailbino/laravel
```

## Configuration

Add your API token to `.env`:

```env
MAIL_MAILER=mailbino

MAILBINO_API_TOKEN=mbn_live_xxxxxxxxxxxx
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=mailbino-config
```

### Mail driver setup

Add the `mailbino` mailer to `config/mail.php`:

```php
'mailers' => [
    'mailbino' => [
        'transport' => 'mailbino',
    ],
    // ...
],
```

That's it. All `Mail::send()` calls now go through Mailbino.

## Usage

### Standard Laravel Mail (zero changes needed)

```php
Mail::to($user)->send(new WelcomeMail($user));
```

### With Mailbino-specific features

Use custom headers to pass tags, metadata, and scoping through the standard mail interface:

```php
Mail::to($user)->send(new InvoiceMail($invoice));

// In your Mailable's headers() method:
public function headers(): Headers
{
    return new Headers(
        text: [
            'X-Mailbino-Tags' => 'invoice, billing',
            'X-Mailbino-Scope-Type' => 'invoice',
            'X-Mailbino-Scope-Id' => $this->invoice->id,
            'X-Mailbino-Metadata' => json_encode(['amount' => $this->invoice->total]),
        ],
    );
}
```

Available headers:

| Header | Description |
|--------|-------------|
| `X-Mailbino-Tags` | Comma-separated tags |
| `X-Mailbino-Scope-Type` | Scope type (e.g. `order`, `user`) |
| `X-Mailbino-Scope-Id` | Scope ID |
| `X-Mailbino-Metadata` | JSON-encoded metadata object |
| `X-Mailbino-Test-Recipient` | Override test recipient for this message |
| `X-Mailbino-External-Id` | Your own message ID (UUID) |

### Direct API client

For full control, use the `Mailbino` facade:

```php
use Mailbino\Laravel\Facades\Mailbino;

// Send an email
$result = Mailbino::send([
    'from' => 'noreply@m.yourapp.com',
    'from_name' => 'Your App',
    'to' => ['email' => 'user@example.com', 'name' => 'John'],
    'subject' => 'Your invoice is ready',
    'html' => '<h1>Invoice</h1>',
    'tags' => ['invoice'],
    'metadata' => ['invoice_id' => 'INV-123'],
    'scope_type' => 'invoice',
    'scope_id' => 'INV-123',
]);

// $result = ['message_id' => '...', 'status' => 'queued']

// Query messages
$messages = Mailbino::messages(['status' => 'delivered', 'to_email' => 'user@example.com']);

// Get a specific message
$message = Mailbino::message('a1b2c3d4-...');

// Get message events
$events = Mailbino::events('a1b2c3d4-...');
```

## Test Mode

Mailbino has built-in test mode — enable it in the Mailbino dashboard and all emails are redirected to your test recipients with a banner showing the original address.

### Option 1: Dashboard

Enable test mode on your app in the Mailbino dashboard and add your test recipients there.

### Option 2: Environment variable

Set a global test recipient in `.env`:

```env
MAILBINO_TEST_RECIPIENT=developer@company.com
```

This sends `test_recipient` on every API call. Combined with test mode on the app, all emails land in your inbox.

### Option 3: Per-message

```php
// Via mail headers
'X-Mailbino-Test-Recipient' => 'developer@company.com'

// Via direct API
Mailbino::send([
    // ...
    'test_recipient' => 'developer@company.com',
]);
```

## Error Handling

```php
use Mailbino\Laravel\MailbinoException;

try {
    Mailbino::send([...]);
} catch (MailbinoException $e) {
    $e->statusCode;  // 422
    $e->apiError;    // "Domain not verified."
    $e->apiMessage;  // "The sender domain for..."
}
```

## License

MIT
