<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Exceptions\ValidationException;
use Bluehost\StripePaymentsAPI\Models\PaymentLink;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;

final class PaymentLinkModelTest extends TestCaseBase
{
    public function testCreateRequiresLineItems(): void
    {
        $this->makeClient();
        $this->expectException(ValidationException::class);
        PaymentLink::create(['active' => true]);
    }

    public function testCreatePaymentLink(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'plink_123',
                    'url' => 'https://buy.stripe.com/test_abc',
                    'active' => true,
                ]),
            ];
        });

        $link = PaymentLink::create([
            'line_items' => [
                ['price' => 'price_123', 'quantity' => 1],
            ],
            'allow_promotion_codes' => true,
        ]);

        $this->assertSame('plink_123', $link->id);
        $this->assertSame('https://buy.stripe.com/test_abc', $link->url);
        $this->assertSame('test/bluehost/payment-link', $this->lastRequest()['path']);
        $this->assertTrue($this->lastRequest()['options']['json']['allow_promotion_codes']);
    }

    public function testDeactivateAndUpdateLineItems(): void
    {
        $this->makeClient(static function ($method, $path) {
            if (str_contains($path, 'line-items')) {
                return [
                    'status' => 200,
                    'headers' => [],
                    'body' => json_encode([
                        'id' => 'plink_123',
                        'active' => true,
                        'line_items' => [['price' => 'price_new', 'quantity' => 2]],
                    ]),
                ];
            }

            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode(['id' => 'plink_123', 'active' => false]),
            ];
        });

        $deactivated = PaymentLink::deactivate('plink_123');
        $this->assertFalse($deactivated->active);
        $this->assertSame('PUT', $this->requests[0]['method']);
        $this->assertSame(['active' => false], $this->requests[0]['options']['json']);

        $updated = PaymentLink::update_line_items('plink_123', [
            ['price' => 'price_new', 'quantity' => 2],
        ]);
        $this->assertSame('plink_123', $updated->id);
        $this->assertSame('POST', $this->lastRequest()['method']);
        $this->assertSame('test/bluehost/payment-link/plink_123/line-items', $this->lastRequest()['path']);
    }

    public function testListPaymentLinks(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'payment_links' => [
                        ['id' => 'plink_1', 'active' => true],
                    ],
                ]),
            ];
        });

        $links = PaymentLink::all(['active' => true]);
        $this->assertCount(1, $links);
        $this->assertSame('test/bluehost/payment-links', $this->lastRequest()['path']);
    }
}
