<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests;

use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Http\TransportInterface;
use PHPUnit\Framework\TestCase;

abstract class TestCaseBase extends TestCase
{
    /** @var array<int, array{method: string, path: string, options: array<string, mixed>}> */
    public array $requests = [];

    public function recordRequest(string $method, string $path, array $options): void
    {
        $this->requests[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'options' => $options,
        ];
    }

    protected function makeClient(?callable $responder = null, $authToken = 'test-token'): StripeClient
    {
        return new StripeClient(
            new Config(
                baseUri: 'https://payments.example.com/api',
                environment: Config::ENVIRONMENT_TEST,
                brand: 'bluehost',
                authToken: $authToken
            ),
            $this->makeTransport($responder)
        );
    }

    protected function makeTransport(?callable $responder = null): TransportInterface
    {
        $this->requests = [];

        return new class ($this, $responder) implements TransportInterface {
            /** @var TestCaseBase */
            private $test;
            /** @var callable|null */
            private $responder;

            public function __construct(TestCaseBase $test, ?callable $responder)
            {
                $this->test = $test;
                $this->responder = $responder;
            }

            public function request(string $method, string $path, array $options = []): array
            {
                $this->test->recordRequest($method, $path, $options);

                if ($this->responder) {
                    return ($this->responder)($method, $path, $options);
                }

                return [
                    'status' => 200,
                    'headers' => [],
                    'body' => json_encode(['ok' => true]),
                ];
            }
        };
    }

    protected function lastRequest(): array
    {
        $this->assertNotEmpty($this->requests);

        return $this->requests[array_key_last($this->requests)];
    }
}
