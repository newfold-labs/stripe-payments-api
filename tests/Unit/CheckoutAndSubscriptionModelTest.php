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
