<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;

/**
 * Stripe publishable key helper. Not meant to be instantiated as a full model.
 */
final class PublicKey extends ModelAbstract
{
    protected static $endpoint = ':env/:brand/public-key';

    private function __construct()
    {
        // Non-instantiatable: the API returns a plain string.
    }

    /**
     * Retrieves the Stripe publishable key for the configured env/brand.
     */
    public static function retrieve(): string
    {
        $raw = StripeClient::call('GET', self::get_endpoint(), []);

        if (is_array($raw)) {
            $raw = $raw['public_key'] ?? $raw['key'] ?? reset($raw);
        }

        return is_string($raw) ? trim($raw) : '';
    }

    /**
     * @param mixed $raw
     */
    protected static function get($raw = false): string
    {
        if (is_array($raw)) {
            $raw = $raw['public_key'] ?? $raw['key'] ?? '';
        }

        return is_string($raw) ? trim($raw) : '';
    }
}
