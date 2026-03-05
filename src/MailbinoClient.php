<?php

namespace Mailbino\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class MailbinoClient
{
    protected Client $http;

    protected string $baseUrl;

    protected ?string $testRecipient;

    public function __construct(
        protected string $apiToken,
        string $baseUrl = 'https://mailbino.com',
        ?string $testRecipient = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');

        // Ensure the base URL ends with /api
        if (! str_ends_with($this->baseUrl, '/api')) {
            $this->baseUrl .= '/api';
        }

        $this->testRecipient = $testRecipient;

        $this->http = new Client([
            'base_uri' => $this->baseUrl . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Send an email via the Mailbino API.
     *
     * @param  array{
     *     from: string,
     *     from_name?: string,
     *     to: string|array,
     *     subject: string,
     *     html?: string,
     *     text?: string,
     *     cc?: array,
     *     bcc?: array,
     *     reply_to?: array,
     *     headers?: array,
     *     tags?: array,
     *     metadata?: array,
     *     scope_type?: string,
     *     scope_id?: string,
     *     external_message_id?: string,
     *     tracking_opens?: bool,
     *     tracking_clicks?: bool,
     *     test_recipient?: string,
     * } $payload
     * @return array{message_id: string, status: string}
     *
     * @throws MailbinoException
     */
    public function send(array $payload): array
    {
        // Inject test_recipient from config if not explicitly set
        if ($this->testRecipient && ! isset($payload['test_recipient'])) {
            $payload['test_recipient'] = $this->testRecipient;
        }

        return $this->post('v1/send', $payload);
    }

    /**
     * List messages with optional filters.
     *
     * @return array{data: array, meta: array}
     *
     * @throws MailbinoException
     */
    public function messages(array $filters = []): array
    {
        return $this->get('v1/messages', $filters);
    }

    /**
     * Get a single message by its external message ID.
     *
     * @throws MailbinoException
     */
    public function message(string $messageId): array
    {
        return $this->get("v1/messages/{$messageId}");
    }

    /**
     * Get the events for a message.
     *
     * @throws MailbinoException
     */
    public function events(string $messageId): array
    {
        return $this->get("v1/messages/{$messageId}/events");
    }

    /**
     * Get an inbound email by ID.
     *
     * @throws MailbinoException
     */
    public function inbound(string $id): array
    {
        return $this->get("v1/inbound/{$id}");
    }

    protected function get(string $uri, array $query = []): array
    {
        try {
            $response = $this->http->get($uri, ['query' => $query]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw MailbinoException::fromResponse($e->getResponse());
        } catch (GuzzleException $e) {
            throw new MailbinoException('Mailbino API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function post(string $uri, array $data): array
    {
        try {
            $response = $this->http->post($uri, ['json' => $data]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw MailbinoException::fromResponse($e->getResponse());
        } catch (GuzzleException $e) {
            throw new MailbinoException('Mailbino API request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
