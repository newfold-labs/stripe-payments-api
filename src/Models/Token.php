<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;

/**
 * JWT auth token for middleware calls.
 *
 * @property string|null $auth
 * @property int|null    $exp
 * @property int|null    $expires_in
 * @property string|null $verify_url
 * @property string|null $site_url
 */
class Token extends ModelAbstract
{
    use ObjectCreateTrait;

    protected static $endpoint = ':env/:brand/token';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'auth' => [
                    'label' => 'Authentication token',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'exp' => [
                    'label' => 'Token expiration',
                    'type' => 'number',
                    'required' => false,
                    'default' => null,
                ],
                'expires_in' => [
                    'label' => 'Token lifetime',
                    'type' => 'number',
                    'required' => false,
                    'default' => null,
                ],
                'site_url' => [
                    'label' => 'Site URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'url',
                ],
                'verify_url' => [
                    'label' => 'Verification URL',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'url',
                ],
            ];
        }

        return self::$data_structure;
    }
}
