<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Client;

use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Exceptions\ApiException;
use Bluehost\StripePaymentsAPI\Exceptions\ProcessorException;
use Bluehost\StripePaymentsAPI\Http\GuzzleTransport;
use Bluehost\StripePaymentsAPI\Http\TransportInterface;
use Bluehost\StripePaymentsAPI\Security\InMemorySignatureStore;
use Bluehost\StripePaymentsAPI\Security\SignatureStoreInterface;
use Bluehost\StripePaymentsAPI\Security\Uuid;

/**
 * HTTP client for the YITH Stripe Payments middleware.
 *
 * Models use {@see self::call()} via a process-wide default instance set with
 * {@see self::setDefault()}, preserving the original Client trait call style.
 */
final class StripeClient
{
    public const SIGNATURE_TTL = 600;

    private static ?self $default = null;

    private Config $config;
    private TransportInterface $transport;
    private SignatureStoreInterface $signatureStore;
    private string $log = '';
    private ?\Exception $lastError = null;

    public function __construct(
        Config $config,
        ?TransportInterface $transport = null,
        ?SignatureStoreInterface $signatureStore = null
    ) {
        $this->config = $config;
        $this->signatureStore = $signatureStore ?? new InMemorySignatureStore();
        $this->transport = $transport ?? new GuzzleTransport($config);
        self::setDefault($this);
    }

    public static function setDefault(self $client): void
    {
        self::$default = $client;
    }

    public static function getDefault(): self
    {
        if (self::$default === null) {
            throw new \RuntimeException(
                'No default StripeClient has been configured. Construct a StripeClient first.'
            );
        }

        return self::$default;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getSignatureStore(): SignatureStoreInterface
    {
        return $this->signatureStore;
    }

    public function getMessageLog(): string
    {
        return $this->log;
    }

    public function getLastError(): ?\Exception
    {
        return $this->lastError;
    }

    public function getLastSignature(): ?string
    {
        return $this->signatureStore->getLast();
    }

    /**
     * @param string|callable|null $authToken
     */
    public function withAuthToken($authToken): self
    {
        return new self(
            $this->config->withAuthToken($authToken),
            $this->transport,
            $this->signatureStore
        );
    }

    public function formatEndpoint(string $endpoint): string
    {
        $placeholders = $this->getEndpointPlaceholders();

        return str_replace(array_keys($placeholders), array_values($placeholders), $endpoint);
    }

    public function hasUnsolvedPlaceholders(string $endpoint): bool
    {
        return (bool) preg_match(
            '/' . implode('|', array_map('preg_quote', array_keys($this->getEndpointPlaceholders()))) . '/',
            $endpoint
        );
    }

    /**
     * Static facade used by model traits (mirrors the original Client::call API).
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>|bool|string|null
     */
    public static function call(string $method, string $endpoint, array $payload = [], array $args = [])
    {
        return self::getDefault()->request($method, $endpoint, $payload, $args);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>|bool|string|null
     *
     * @throws ApiException
     * @throws ProcessorException
     */
    public function request(string $method, string $endpoint, array $payload = [], array $args = [])
    {
        try {
            return $this->processRequest($method, $endpoint, $payload, $args);
        } catch (\Exception $e) {
            $this->processError($e, $method, $endpoint, $payload);
        }
    }

    /**
     * @return array<string, string>
     */
    private function getEndpointPlaceholders(): array
    {
        return [
            ':brand' => $this->config->getBrand(),
            ':env' => $this->config->getEnvironment(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>|bool|string|null
     */
    private function processRequest(string $method, string $endpoint, array $payload, array $args)
    {
        $endpoint = $this->formatEndpoint($endpoint);

        if ($this->hasUnsolvedPlaceholders($endpoint)) {
            throw new \InvalidArgumentException(
                'It is not possible to perform a call on an endpoint with an unsolved placeholder.'
            );
        }

        $method = strtoupper($method);
        $headers = array_merge(
            [
                'Accept' => 'application/json',
                'User-Agent' => $this->config->getUserAgent(),
            ],
            $args['headers'] ?? []
        );

        $signature = Uuid::v4();
        $headers['X-Request-Signature'] = $signature;
        $this->signatureStore->put($signature, self::SIGNATURE_TTL);

        $auth = $this->config->resolveAuthToken();
        if ($auth !== null) {
            $headers['Authorization'] = 'Bearer ' . $auth;
        }

        $options = [
            'headers' => $headers,
            'timeout' => $args['timeout'] ?? $this->config->getTimeout(),
        ];

        if ($method === 'GET' || $method === 'DELETE') {
            if ($payload !== []) {
                $options['query'] = $payload;
            }
        } else {
            $options['json'] = $payload;
        }

        $response = $this->transport->request($method, $endpoint, $options);

        return $this->processAnswer($response, $method, $endpoint, $payload);
    }

    /**
     * @param array{status: int, headers: array<string, mixed>, body: string} $response
     * @param array<string, mixed>                                             $payload
     *
     * @return array<string, mixed>|bool|string|null
     */
    private function processAnswer(array $response, string $method, string $endpoint, array $payload)
    {
        $this->lastError = null;
        $status = (int) ($response['status'] ?? 0);
        $rawBody = $response['body'] ?? '';
        $body = $rawBody !== '' ? json_decode($rawBody, true) : null;

        if ($rawBody !== '' && $body === null && json_last_error() !== JSON_ERROR_NONE) {
            $body = $rawBody;
        }

        $message = "{$method} /{$endpoint}";

        if ($status >= 200 && $status < 300) {
            $this->log = $message . " {$status}\n";
            return $body ?? true;
        }

        $errorMessage = is_array($body) && isset($body['message'])
            ? (string) $body['message']
            : (is_string($body) ? $body : 'Unexpected API response');
        $code = is_array($body) && isset($body['code']) ? (string) $body['code'] : 'unknown';

        $exception = new ApiException(
            $errorMessage,
            $status,
            $code,
            [
                'path' => $endpoint,
                'method' => $method,
                'payload' => $this->redactPayload($payload),
                'response' => [
                    'status' => $status,
                    // Redacted like the request payload: an error body can echo back
                    // submitted fields (e.g. a validation error on a `secret`/`client_secret`
                    // value), and these details flow into logs and, via WP_Error, back to
                    // admin AJAX callers. Raw (non-JSON) bodies are left as-is: no structured
                    // keys to redact.
                    'body' => is_array($body) ? $this->redactPayload($body) : $rawBody,
                ],
                'severity' => is_array($body) ? ($body['severity'] ?? '') : '',
                'errors' => is_array($body) ? ($body['errors'] ?? []) : [],
                'data' => is_array($body) ? ($body['data'] ?? []) : [],
            ]
        );

        $this->log = $message . " {$status}\n{$errorMessage}\n";
        $this->lastError = $exception;

        throw $exception;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return never
     */
    private function processError(\Exception $error, string $method, string $endpoint, array $payload): void
    {
        $this->log = sprintf(
            "%s /%s %s\n%s\n",
            $method,
            $endpoint,
            $error->getCode(),
            $error->getMessage()
        );
        $this->lastError = $error;

        throw $error;
    }

    /**
     * Redacts sensitive keys, recursively, from a payload or response body before it is
     * attached to an exception (and from there into logs and any WP_Error surfaced to callers).
     *
     * @param array<string|int, mixed> $payload
     *
     * @return array<string|int, mixed>
     */
    private function redactPayload(array $payload): array
    {
        $sensitive = ['auth', 'secret', 'password', 'client_secret', 'authorization'];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->redactPayload($value);
                continue;
            }

            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
}
