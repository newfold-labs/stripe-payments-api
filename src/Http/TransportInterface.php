<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Http;

/**
 * HTTP transport contract for the payments middleware client.
 */
interface TransportInterface
{
    /**
     * Performs an HTTP request against a path relative to the configured base URI.
     *
     * @param string               $method  HTTP method.
     * @param string               $path    Relative path (no leading slash required).
     * @param array<string, mixed> $options Keys: `headers` (array), `query` (array), `json` (array|null), `body` (string|null), `timeout` (int).
     *
     * @return array{status: int, headers: array<string, mixed>, body: string}
     *
     * @throws \Bluehost\StripePaymentsAPI\Exceptions\ProcessorException
     */
    public function request(string $method, string $path, array $options = []): array;
}
