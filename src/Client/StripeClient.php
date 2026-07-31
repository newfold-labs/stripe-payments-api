<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Client;

use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Exceptions\ApiException;
use Bluehost\StripePaymentsAPI\Exceptions\ProcessorException;
use Bluehost\StripePaymentsAPI\Http\GuzzleTransport;
use Bluehost\StripePaymentsAPI\Http\TransportInterface;
use Bluehost\StripePaymentsAPI\Store\InMemoryStore;
use Bluehost\StripePaymentsAPI\Store\StoreInterface;
use Ramsey\Uuid\Uuid;

/**
 * HTTP client for the YITH Stripe Payments middleware.
 *
 * Models use {@see self::call()} via a process-wide default instance set with
 * {@see self::setDefault()}, preserving the original Client trait call style.
 */
final class StripeClient
{
    public const SIGNATURE_TTL = 600;

    private const SIGNATURE_KEY_PREFIX = 'signature.';
    private const LAST_SIGNATURE_KEY = 'signature.last';
    private const ACCOUNT_TOKEN_KEY = 'account.token';

    private static ?self $default = null;

    private Config $config;
    private TransportInterface $transport;
    private StoreInterface $store;
    private string $log = '';
    private ?\Exception $lastError = null;

    public function __construct(
        Config $config,
        ?TransportInterface $transport = null,
        ?StoreInterface $store = null
    ) {
        $this->config = $config;
        $this->store = $store ?? new InMemoryStore();
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

    public function getStore(): StoreInterface
    {
        return $this->store;
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
        $signature = $this->store->get(self::LAST_SIGNATURE_KEY);

        return is_string($signature) && $signature !== '' ? $signature : null;
    }

    /**
     * Verifies a signature challenge against a request signature this client
     * previously sent, e.g. from a middleware callback endpoint.
     */
    public function verifySignature(string $signature): bool
    {
        if ($signature === '') {
            return false;
        }

        return $this->store->get(self::signatureKey($signature)) !== null;
    }

    /**
     * Static facade, mirroring {@see self::call()}.
     */
    public static function verify(string $signature): bool
    {
        return self::getDefault()->verifySignature($signature);
    }

    /**
     * Stores the bearer token for the currently connected account, so it is
     * picked up automatically on subsequent requests without callers having
     * to pass it (or a callable resolving it) on every call.
     *
     * @param int $ttl Time-to-live in seconds. 0 means "no expiration".
     */
    public function setAccountToken(?string $token, int $ttl = 0): void
    {
        if ($token === null || $token === '') {
            $this->clearAccountToken();
            return;
        }

        $this->store->set(self::ACCOUNT_TOKEN_KEY, $token, $ttl);
    }

    /**
     * Returns the bearer token stored for the currently connected account, if any.
     */
    public function getAccountToken(): ?string
    {
        $token = $this->store->get(self::ACCOUNT_TOKEN_KEY);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function clearAccountToken(): void
    {
        $this->store->delete(self::ACCOUNT_TOKEN_KEY);
    }

    /**
     * @param string|callable|null $authToken
     */
    public function withAuthToken($authToken): self
    {
        return new self(
            $this->config->withAuthToken($authToken),
            $this->transport,
            $this->store
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

        $signature = Uuid::uuid4()->toString();
        $headers['X-Request-Signature'] = $signature;
        $this->store->set(self::signatureKey($signature), true, self::SIGNATURE_TTL);
        $this->store->set(self::LAST_SIGNATURE_KEY, $signature, self::SIGNATURE_TTL);

        // An explicitly configured token (string or callable) always wins; otherwise
        // fall back to whatever token is on record for the currently connected account.
        $auth = $this->config->resolveAuthToken() ?? $this->getAccountToken();
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
     * Namespaced, fixed-length store key for a given signature, so raw UUIDs never
     * end up as literal key material in whatever store implementation is in use.
     */
    private static function signatureKey(string $signature): string
    {
        return self::SIGNATURE_KEY_PREFIX . md5($signature);
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
