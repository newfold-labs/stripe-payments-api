<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Exceptions\ApiException;
use Bluehost\StripePaymentsAPI\Exceptions\ValidationException;
use Bluehost\StripePaymentsAPI\Models\Customer;
use Bluehost\StripePaymentsAPI\Models\PaymentMethod;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;

/**
 * Regression tests for the security fixes: path-segment traversal, https
 * enforcement on Config, and recursive redaction of sensitive fields.
 */
final class SecurityFixesTest extends TestCaseBase
{
    public function testTraversalIdIsRejected(): void
    {
        $this->makeClient();

        $this->expectException(ValidationException::class);
        Customer::read('../../admin-secret');
    }

    public function testEmptyPathSegmentIsRejected(): void
    {
        $this->makeClient();

        $this->expectException(ValidationException::class);
        PaymentMethod::attach('pm_123//attach', 'cus_1');
    }

    public function testPercentEncodedTraversalIsRejected(): void
    {
        $this->makeClient();

        $this->expectException(ValidationException::class);
        Customer::read('%2e%2e/%2e%2e/admin-secret');
    }

    public function testWellFormedIdWithSubpathIsUnaffected(): void
    {
        $this->makeClient();

        Customer::get_payment_methods('cus_123', ['limit' => 1]);

        $this->assertSame('test/bluehost/customer/cus_123/payment-methods', $this->lastRequest()['path']);
    }

    public function testConfigRejectsPlainHttpByDefault(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Config(
            baseUri: 'http://payments.example.com/api',
            environment: Config::ENVIRONMENT_TEST,
            brand: 'bluehost'
        );
    }

    public function testConfigAllowsPlainHttpWhenExplicitlyOptedIn(): void
    {
        $config = new Config(
            baseUri: 'http://localhost:8080/api',
            environment: Config::ENVIRONMENT_TEST,
            brand: 'bluehost',
            authToken: null,
            userAgent: null,
            timeout: 30,
            verifySsl: true,
            allowInsecureHttp: true
        );

        $this->assertSame('http://localhost:8080/api', $config->getBaseUri());
    }

    public function testResponseBodySecretsAreRedactedInExceptionDetails(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 400,
                'headers' => [],
                'body' => json_encode([
                    'message' => 'Bad request',
                    'code' => 'invalid_request',
                    'data' => [
                        'secret' => 'super-sensitive-value',
                        'nested' => ['client_secret' => 'also-sensitive'],
                    ],
                ]),
            ];
        });

        try {
            Customer::create(['email' => 'a@b.com']);
            $this->fail('Expected ApiException was not thrown.');
        } catch (ApiException $e) {
            $body = $e->getDetails()['response']['body'];
            $this->assertSame('[redacted]', $body['data']['secret']);
            $this->assertSame('[redacted]', $body['data']['nested']['client_secret']);
        }
    }
}
