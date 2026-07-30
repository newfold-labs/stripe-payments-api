<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;

/**
 * Stripe Connect OAuth token exchange.
 *
 * @property string|null $stripe_user_id
 * @property string|null $secret
 * @property string|null $code
 * @property string|null $grant_type
 */
class AuthToken extends ModelAbstract
{
    use ObjectCreateTrait;

    protected static $endpoint = ':env/:brand/connect/token';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'secret' => [
                    'label' => 'Secret key',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'code' => [
                    'label' => 'Authorization code',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'grant_type' => [
                    'label' => 'Grant type',
                    'type' => 'text',
                    'required' => false,
                    'default' => 'authorization_code',
                ],
                'stripe_user_id' => [
                    'label' => 'Stripe user ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return static
     */
    public static function create(array $args = [])
    {
        $response = StripeClient::call('POST', self::get_endpoint(), self::get_data($args));

        return self::get(is_array($response) ? $response : []);
    }
}
