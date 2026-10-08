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

    public function testReconnectPostsToAccountSubpath(): void
    {
        $this->makeClient(static function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_encode([
                    'acct_id' => 'acct_12345678901234567890',
                    'site_url' => 'https://new.example.com',
                    'token' => ['auth' => 'jwt-token', 'expires_in' => 3600],
                ]),
            ];
        });

        $account = Account::reconnect('acct_12345678901234567890', [
            'site_url' => 'https://new.example.com',
            'verify_url' => 'https://new.example.com/verify',
            'return_url' => 'https://new.example.com/return',
            'webhook_url' => 'https://new.example.com/webhook',
        ]);

        $request = $this->lastRequest();

        $this->assertSame('POST', $request['method']);
        $this->assertSame(
            'test/bluehost/account/acct_12345678901234567890/reconnect',
            $request['path']
        );
        $this->assertSame('https://new.example.com', $request['options']['json']['site_url']);
        $this->assertSame('https://new.example.com/webhook', $request['options']['json']['webhook_url']);
        $this->assertSame('acct_12345678901234567890', $account->acct_id);
    }
}
