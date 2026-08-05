<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Models\Customer;

use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;
use Ramsey\Uuid\Uuid;

final class ModelValidationTest extends TestCaseBase
{
    public function testHashSanitizationWritesBack(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode(['id' => 'cus_1', 'metadata' => ['a' => 'b']]),
            ];
        });

        Customer::create([
            'email' => 'jane@example.com',
            'metadata' => ['order_id' => ' 123 ', 'nested' => ['x' => '<b>y</b>']],
        ]);

        $json = $this->lastRequest()['options']['json'];
        $this->assertSame('123', $json['metadata']['order_id']);
        $this->assertSame('y', $json['metadata']['nested']['x']);
    }

    public function testReadableModelsSupportExpansion(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode(['id' => 'cus_1']),
            ];
        });

        Customer::read('cus_1', ['invoice_settings.default_payment_method']);

        $this->assertSame(
            ['invoice_settings.default_payment_method'],
            $this->lastRequest()['options']['query']['expand']
        );
    }

    public function testUuidHelper(): void
    {
        $id = Uuid::uuid4()->toString();
        $this->assertTrue(Uuid::isValid($id));
        $this->assertFalse(Uuid::isValid('not-a-uuid'));
    }
}
