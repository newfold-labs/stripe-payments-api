<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Http;

use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Exceptions\ProcessorException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/**
 * Guzzle-backed HTTP transport. Returns a raw status/headers/body tuple;
 * JSON decoding is the caller's responsibility (avoids double-decode bugs).
 */
final class GuzzleTransport implements TransportInterface
{
    private GuzzleClient $client;

    public function __construct(private Config $config, ?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient([
            'base_uri' => rtrim($this->config->getBaseUri(), '/') . '/',
            'http_errors' => false,
            'verify' => $this->config->shouldVerifySsl(),
            'timeout' => $this->config->getTimeout(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $method = strtoupper($method);
        $path = ltrim($path, '/');

        $guzzleOptions = [
            'headers' => $options['headers'] ?? [],
            'timeout' => $options['timeout'] ?? $this->config->getTimeout(),
            'verify' => $this->config->shouldVerifySsl(),
            'http_errors' => false,
        ];

        if (! empty($options['query']) && is_array($options['query'])) {
            $guzzleOptions['query'] = $options['query'];
        }

        if (array_key_exists('json', $options) && $options['json'] !== null) {
            $guzzleOptions['json'] = $options['json'];
        } elseif (isset($options['body'])) {
            $guzzleOptions['body'] = $options['body'];
        }

        try {
            $response = $this->client->request($method, $path, $guzzleOptions);
        } catch (RequestException $e) {
            $message = $e->getMessage();
            throw new ProcessorException($message !== '' ? $message : 'HTTP request failed');
        } catch (GuzzleException $e) {
            throw new ProcessorException($e->getMessage());
        }

        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
        ];
    }
}
