<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Exceptions\ValidationException;
use Bluehost\StripePaymentsAPI\Models\BillingPortalSession;
use Bluehost\StripePaymentsAPI\Models\CheckoutSession;
use Bluehost\StripePaymentsAPI\Models\Subscription;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;

final class CheckoutAndSubscriptionModelTest extends TestCaseBase
{
    public function testCreateCheckoutSession(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'cs_test_123',
                    'status' => 'open',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
                ]),
            ];
        });

        $session = CheckoutSession::create([
            'mode' => 'subscription',
            'line_items' => [
                ['price' => 'price_123', 'quantity' => 1],
            ],
            'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://example.com/cancel',
            'metadata' => ['plan' => ' pro '],
            'subscription_data' => ['metadata' => ['source' => ' checkout ']],
        ]);

        $this->assertSame('cs_test_123', $session->id);
        $this->assertSame('POST', $this->lastRequest()['method']);
        $this->assertSame('test/bluehost/checkout-session', $this->lastRequest()['path']);
        $this->assertSame('pro', $this->lastRequest()['options']['json']['metadata']['plan']);
        $this->assertSame(
            'checkout',
            $this->lastRequest()['options']['json']['subscription_data']['metadata']['source']
        );
    }

    public function testCreateCheckoutSessionSendsCollectionOptions(): void
    {
        $this->makeClient(static function () {
            return ['status' => 200, 'headers' => [], 'body' => json_encode(['id' => 'cs_test_123'])];
        });

        CheckoutSession::create([
            'mode' => 'payment',
            'line_items' => [['price' => 'price_123', 'quantity' => 1]],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'customer' => 'cus_123',
            'billing_address_collection' => 'required',
            'phone_number_collection' => ['enabled' => true],
            'shipping_address_collection' => ['allowed_countries' => ['IT', 'US']],
            'customer_update' => ['address' => 'auto', 'name' => 'auto', 'shipping' => 'auto'],
        ]);

        $json = $this->lastRequest()['options']['json'];

        $this->assertSame('required', $json['billing_address_collection']);
        $this->assertSame(['enabled' => true], $json['phone_number_collection']);
        $this->assertSame(['allowed_countries' => ['IT', 'US']], $json['shipping_address_collection']);
        $this->assertSame(['address' => 'auto', 'name' => 'auto', 'shipping' => 'auto'], $json['customer_update']);
    }

    public function testCreateCheckoutSessionKeepsPhoneCollectionDisabled(): void
    {
        $this->makeClient(static function () {
            return ['status' => 200, 'headers' => [], 'body' => json_encode(['id' => 'cs_test_123'])];
        });

        CheckoutSession::create([
            'mode' => 'payment',
            'line_items' => [['price' => 'price_123', 'quantity' => 1]],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'phone_number_collection' => ['enabled' => false],
        ]);

        $this->assertSame(['enabled' => false], $this->lastRequest()['options']['json']['phone_number_collection']);
    }

    public function testCreateCheckoutSessionOmitsUnsetCollectionOptions(): void
    {
        $this->makeClient(static function () {
            return ['status' => 200, 'headers' => [], 'body' => json_encode(['id' => 'cs_test_123'])];
        });

        CheckoutSession::create([
            'mode' => 'payment',
            'line_items' => [['price' => 'price_123', 'quantity' => 1]],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $json = $this->lastRequest()['options']['json'];

        foreach (['billing_address_collection', 'phone_number_collection', 'shipping_address_collection', 'customer_update'] as $key) {
            $this->assertArrayNotHasKey($key, $json);
        }
    }

    /**
     * @dataProvider invalidCollectionOptions
     *
     * @param array<string, mixed> $extra
     */
    public function testCheckoutSessionRejectsInvalidCollectionOptions(array $extra): void
    {
        $this->makeClient();
        $this->expectException(ValidationException::class);

        CheckoutSession::create(array_merge([
            'mode' => 'payment',
            'line_items' => [['price' => 'price_123', 'quantity' => 1]],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ], $extra));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidCollectionOptions(): array
    {
        return [
            'billing address value' => [['billing_address_collection' => 'always']],
            'customer_update value' => [['customer_update' => ['shipping' => 'yes']]],
            'no shipping countries' => [['shipping_address_collection' => ['allowed_countries' => []]]],
            'shipping not an object' => [['shipping_address_collection' => 'IT']],
        ];
    }

    public function testCheckoutSessionRequiresValidCreateFields(): void
    {
        $this->makeClient();
        $this->expectException(ValidationException::class);

        CheckoutSession::create([
            'mode' => 'setup',
            'line_items' => [['price' => 'price_123']],
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ]);
    }

    public function testReadCheckoutSessionWithExpansion(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'cs_test_123',
                    'subscription' => ['id' => 'sub_123', 'status' => 'active'],
                ]),
            ];
        });

        $session = CheckoutSession::read('cs_test_123', ['subscription']);

        $this->assertSame('sub_123', $session->subscription['id']);
        $this->assertSame('GET', $this->lastRequest()['method']);
        $this->assertSame('test/bluehost/checkout-session/cs_test_123', $this->lastRequest()['path']);
        $this->assertSame(['subscription'], $this->lastRequest()['options']['query']['expand']);
    }

    public function testReadSubscriptionWithExpansion(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'sub_123',
                    'customer' => 'cus_123',
                    'status' => 'active',
                    'items' => ['data' => [['id' => 'si_123']]],
                ]),
            ];
        });

        $subscription = Subscription::read('sub_123', ['items.data.price']);

        $this->assertSame('active', $subscription->status);
        $this->assertSame('si_123', $subscription->items['data'][0]['id']);
        $this->assertSame('test/bluehost/subscription/sub_123', $this->lastRequest()['path']);
        $this->assertSame(['items.data.price'], $this->lastRequest()['options']['query']['expand']);
    }

    public function testCreateBillingPortalSession(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'bps_123',
                    'url' => 'https://billing.stripe.com/p/session/bps_123',
                ]),
            ];
        });

        $session = BillingPortalSession::create([
            'customer' => 'cus_123',
            'return_url' => 'https://example.com/account',
        ]);

        $this->assertSame('bps_123', $session->id);
        $this->assertSame('POST', $this->lastRequest()['method']);
        $this->assertSame('test/bluehost/billing-portal-session', $this->lastRequest()['path']);
        $this->assertSame('cus_123', $this->lastRequest()['options']['json']['customer']);
    }
}
