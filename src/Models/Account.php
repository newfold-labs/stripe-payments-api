<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectDeleteTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;

/**
 * Connected Stripe account onboarding record.
 *
 * @property string      $site_url
 * @property string      $verify_url
 * @property string|null $return_url
 * @property string|null $webhook_url
 * @property string|null $acct_id
 * @property string|null $secret
 * @property string|null $env
 * @property string|null $country
 * @property string|null $email
 * @property string|null $onboard_link
 * @property int|null    $onboard_exp
 * @property bool|null   $pmd_enabled
 * @property array|null  $pmd_statuses
 * @property bool|null   $charges_enabled
 * @property bool|null   $details_submitted
 * @property Token|null  $token
 */
class Account extends ModelAbstract
{
    use ObjectReadTrait;
    use ObjectCreateTrait;
    use ObjectDeleteTrait;

    protected static $endpoint = ':env/:brand/account';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'site_url' => [
                    'label' => 'Site URL',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'url',
                ],
                'acct_id' => [
                    'label' => 'Stripe account ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'country' => [
                    'label' => 'Site country',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'country',
                ],
                'env' => [
                    'label' => 'Account environment',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'secret' => [
                    'label' => 'Account secret',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'email' => [
                    'label' => 'Admin email',
                    'type' => 'email',
                    'required' => false,
                    'default' => null,
                ],
                'onboard_link' => [
                    'label' => 'Onboarding link',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'url',
                ],
                'onboard_exp' => [
                    'label' => 'Onboarding link expiration',
                    'type' => 'number',
                    'required' => false,
                    'default' => null,
                ],
                'pmd_enabled' => [
                    'label' => 'Domain enabled',
                    'type' => 'bool',
                    'required' => false,
                    'default' => false,
                ],
                'pmd_statuses' => [
                    'label' => 'Payment Method statuses for Domain',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'charges_enabled' => [
                    'label' => 'Charges enabled flag',
                    'type' => 'bool',
                    'required' => false,
                    'default' => false,
                ],
                'details_submitted' => [
                    'label' => 'Details submitted flag',
                    'type' => 'bool',
                    'required' => false,
                    'default' => false,
                ],
                'verify_url' => [
                    'label' => 'Verification URL',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'url',
                ],
                'webhook_url' => [
                    'label' => 'Webhook URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                    'validation' => 'url',
                ],
                'return_url' => [
                    'label' => 'Return URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'url',
                ],
                'token' => [
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
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return static
     */
    protected static function get(array $raw = [])
    {
        $instance = parent::get($raw);
        $instance->token = self::get_token($raw);

        return $instance;
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected static function get_token(array $raw): ?Token
    {
        if (! isset($raw['token']) || ! is_array($raw['token'])) {
            return null;
        }

        try {
            return new Token($raw['token']);
        } catch (\Exception $e) {
            return null;
        }
    }
}
