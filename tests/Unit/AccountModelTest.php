<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Tests\Unit;

use Bluehost\StripePaymentsAPI\Models\Account;
use Bluehost\StripePaymentsAPI\Tests\TestCaseBase;

/**
 * Regression test: Account::create() must never send response-only fields
 * (secret, pmd_enabled, charges_enabled, details_submitted) — the middleware
 * throws an internal error ("Cannot read properties of undefined (reading
 * 'toString')") when it receives them on account creation.
 */
final class AccountModelTest extends TestCaseBase
{
    public function testCreatePayloadOmitsResponseOnlyFields(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode(['acct_id' => 'acct_123']),
            ];
        });

        Account::create([
            'site_url' => 'https://example.com',
            'verify_url' => 'https://example.com/verify',
            'return_url' => 'https://example.com/return',
            'webhook_url' => 'https://example.com/webhook',
        ]);

        $json = $this->lastRequest()['options']['json'];

        foreach (['secret', 'pmd_enabled', 'charges_enabled', 'details_submitted'] as $responseOnlyField) {
            $this->assertArrayNotHasKey($responseOnlyField, $json, "'$responseOnlyField' must not be sent on create()");
        }

        $this->assertSame('https://example.com', $json['site_url']);
        $this->assertSame('https://example.com/verify', $json['verify_url']);
    }
}
