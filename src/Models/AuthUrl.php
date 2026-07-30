<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;

/**
 * Stripe Connect OAuth authorization URL.
 *
 * @property string|null $auth_url
 * @property string|null $client_id
 * @property string|null $redirect_uri
 */
class AuthUrl extends ModelAbstract
{
    protected static $endpoint = ':env/:brand/connect/auth-url';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'client_id' => [
                    'label' => 'Client ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'redirect_uri' => [
                    'label' => 'Return URI',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'auth_url' => [
                    'label' => 'Auth URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * Fetches the Connect auth URL (middleware exposes this as GET).
     *
     * @param array<string, mixed> $args
     *
     * @return static
     */
    public static function create(array $args = [])
    {
        $result = StripeClient::call('GET', self::get_endpoint(), self::get_data($args));
        if (! is_array($result)) {
            $result = [];
        }
        if (isset($result['auth_url']) && is_string($result['auth_url'])) {
            $result['auth_url'] = urldecode($result['auth_url']);
        }

        return self::get($result);
    }
}
