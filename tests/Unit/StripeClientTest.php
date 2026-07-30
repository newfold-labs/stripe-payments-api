<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Exceptions\ApiException;
use Bluehost\StripePaymentsAPI\Models\Customer;
use Bluehost\StripePaymentsAPI\Security\Uuid;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;

final class StripeClientTest extends TestCaseBase
{
    public function testFormatsEndpointPlaceholders(): void
    {
        $client = $this->makeClient();
        $this->assertSame(
            'test/bluehost/customer',
            $client->formatEndpoint(':env/:brand/customer')
        );
    }

    public function testAddsAuthAndSignatureHeaders(): void
    {
        $this->makeClient();
        Customer::create(['email' => 'jane@example.com']);

        $request = $this->lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame('test/bluehost/customer', $request['path']);
        $this->assertSame('Bearer test-token', $request['options']['headers']['Authorization']);
        $this->assertTrue(Uuid::isValid($request['options']['headers']['X-Request-Signature']));
    }

    public function testTreats2xxAsSuccess(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 201,
                'headers' => [],
                'body' => json_encode(['id' => 'cus_123', 'email' => 'a@b.com']),
            ];
        });

        $customer = Customer::create(['email' => 'a@b.com']);
        $this->assertSame('cus_123', $customer->id);
    }

    public function testThrowsApiExceptionOnErrorStatus(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 400,
                'headers' => [],
                'body' => json_encode([
                    'message' => 'Bad request',
                    'code' => 'invalid_request',
                    'errors' => ['email'],
                ]),
            ];
        });

        $this->expectException(ApiException::class);
        Customer::create(['email' => 'valid@example.com', 'name' => 'Fail']);
    }

    public function testConfigRejectsInvalidEnvironment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Config(
            baseUri: 'https://payments.example.com/api',
            environment: 'prod',
            brand: 'bluehost'
        );
    }
}
