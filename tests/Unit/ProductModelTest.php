<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Exceptions\ValidationException;
use Bluehost\StripePaymentsAPI\Models\Price;
use Bluehost\StripePaymentsAPI\Models\Product;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;

final class ProductModelTest extends TestCaseBase
{
    public function testCreateProductRequiresName(): void
    {
        $this->makeClient();
        $this->expectException(ValidationException::class);
        Product::create(['description' => 'missing name']);
    }

    public function testCreateProductWithNestedPrice(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'prod_123',
                    'name' => 'Pro Plan',
                    'default_price' => 'price_123',
                    'active' => true,
                ]),
            ];
        });

        $product = Product::create([
            'name' => 'Pro Plan',
            'price' => [
                'currency' => 'USD',
                'unit_amount' => 4900,
                'recurring' => ['interval' => 'month'],
            ],
        ]);

        $this->assertSame('prod_123', $product->id);
        $request = $this->lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame('test/bluehost/product', $request['path']);
        $this->assertSame('usd', $request['options']['json']['price']['currency']);
        $this->assertSame(4900, $request['options']['json']['price']['unit_amount']);
    }

    public function testListAndArchiveProduct(): void
    {
        $this->makeClient(static function ($method, $path) {
            if (str_contains($path, 'products')) {
                return [
                    'status' => 200,
                    'headers' => [],
                    'body' => json_encode([
                        'products' => [
                            ['id' => 'prod_1', 'name' => 'A'],
                            ['id' => 'prod_2', 'name' => 'B'],
                        ],
                    ]),
                ];
            }

            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode(['id' => 'prod_1', 'active' => false]),
            ];
        });

        $list = Product::all(['limit' => 10]);
        $this->assertCount(2, $list);
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('test/bluehost/products', $this->requests[0]['path']);

        $archived = Product::archive('prod_1');
        $this->assertFalse($archived->active);
        $this->assertSame('DELETE', $this->lastRequest()['method']);
        $this->assertSame('test/bluehost/product/prod_1', $this->lastRequest()['path']);
    }

    public function testCreatePriceForProduct(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'id' => 'price_abc',
                    'currency' => 'usd',
                    'unit_amount' => 1000,
                    'product' => 'prod_123',
                ]),
            ];
        });

        $price = Price::create_for_product('prod_123', [
            'currency' => 'usd',
            'unit_amount' => 1000,
        ]);

        $this->assertSame('price_abc', $price->id);
        $this->assertSame('test/bluehost/product/prod_123/price', $this->lastRequest()['path']);
    }
}
