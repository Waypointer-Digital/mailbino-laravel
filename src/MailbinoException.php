<?php

namespace Mailbino\Laravel;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class MailbinoException extends RuntimeException
{
    public ?string $apiError;

    public ?string $apiMessage;

    public int $statusCode;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->apiError = null;
        $this->apiMessage = null;
        $this->statusCode = $code;
    }

    public static function fromResponse(ResponseInterface $response): static
    {
        $body = json_decode($response->getBody()->getContents(), true) ?? [];
        $statusCode = $response->getStatusCode();

        $error = $body['error'] ?? 'Unknown error';
        $message = $body['message'] ?? '';

        $exception = new static(
            "Mailbino API error [{$statusCode}]: {$error}" . ($message ? " — {$message}" : ''),
            $statusCode,
        );

        $exception->apiError = $error;
        $exception->apiMessage = $message;
        $exception->statusCode = $statusCode;

        return $exception;
    }
}
