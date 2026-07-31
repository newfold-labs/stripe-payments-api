<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Exceptions\ApiException;
use Bluehost\StripePaymentsAPI\Models\Customer;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;
use Ramsey\Uuid\Uuid;

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

    public function testVerifySignatureAcceptsASignatureItJustSent(): void
    {
        $client = $this->makeClient();
        Customer::create(['email' => 'jane@example.com']);

        $signature = $this->lastRequest()['options']['headers']['X-Request-Signature'];

        $this->assertTrue($client->verifySignature($signature));
        $this->assertTrue(StripeClient::verify($signature));
        $this->assertSame($signature, $client->getLastSignature());
    }

    public function testVerifySignatureRejectsAnUnknownSignature(): void
    {
        $client = $this->makeClient();

        $this->assertFalse($client->verifySignature(Uuid::uuid4()->toString()));
        $this->assertFalse($client->verifySignature(''));
    }

    public function testAccountTokenIsUsedWhenConfigHasNoExplicitAuthToken(): void
    {
        $client = $this->makeClient(authToken: null);

        $client->setAccountToken('connected-account-token');
        Customer::create(['email' => 'jane@example.com']);

        $this->assertSame(
            'Bearer connected-account-token',
            $this->lastRequest()['options']['headers']['Authorization']
        );

        $client->clearAccountToken();
        $this->assertNull($client->getAccountToken());
    }

    public function testExplicitAuthTokenOverridesTheStoredAccountToken(): void
    {
        $client = $this->makeClient();
        $client->setAccountToken('should-be-ignored');

        Customer::create(['email' => 'jane@example.com']);

        $this->assertSame('Bearer test-token', $this->lastRequest()['options']['headers']['Authorization']);
    }
}
