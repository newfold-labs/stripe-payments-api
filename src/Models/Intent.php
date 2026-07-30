<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectUpdateTrait;

/**
 * Stripe PaymentIntent via the payments middleware.
 *
 * @property string|null $id
 * @property int|null    $amount
 * @property string|null $currency
 * @property string|null $customer
 * @property string|null $status
 * @property string|null $client_secret
 * @property string|null $payment_method
 * @property array|null  $metadata
 */
class Intent extends ModelAbstract
{
    use ObjectReadTrait;
    use ObjectCreateTrait;
    use ObjectUpdateTrait;

    protected static $endpoint = ':env/:brand/intent';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    /** @var array<string, mixed>|null */
    protected static $confirm_data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Intent ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'amount' => [
                    'label' => 'Intent amount',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0,
                ],
                'amount_capturable' => [
                    'label' => 'Capturable amount',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0,
                ],
                'automatic_payment_methods' => [
                    'enabled' => [
                        'label' => 'Automatic methods flag',
                        'type' => 'bool',
                        'required' => false,
                        'default' => true,
                    ],
                ],
                'capture_method' => [
                    'label' => 'Capture method',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'confirm' => [
                    'label' => 'Confirm flag',
                    'type' => 'bool',
                    'required' => false,
                    'default' => false,
                ],
                'client_secret' => [
                    'label' => 'Client secret',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'currency' => [
                    'label' => 'Intent currency',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                    'validation' => 'currency',
                ],
                'customer' => [
                    'label' => 'Customer',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'description' => [
                    'label' => 'Intent description',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'last_payment_error' => [
                    'label' => 'Last payment error',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'latest_charge' => [
                    'label' => 'Last charge',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'mandate_data' => [
                    'label' => 'Mandate',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'metadata' => [
                    'label' => 'Intent metadata',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'next_action' => [
                    'label' => 'Next action details',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'payment_method' => [
                    'label' => 'Payment method',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'payment_method_types' => [
                    'label' => 'Payment method types',
                    'type' => 'array',
                    'required' => false,
                    'default' => null,
                ],
                'receipt_email' => [
                    'label' => 'Receipt email',
                    'type' => 'email',
                    'required' => false,
                    'default' => '',
                ],
                'return_url' => [
                    'label' => 'Return URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'url',
                ],
                'setup_future_usage' => [
                    'label' => 'Setup future usage flag',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'status' => [
                    'label' => 'Status',
                    'type' => 'text',
                    'required' => false,
                    'default' => 'requires_confirmation',
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_confirm_data_structure(): array
    {
        if (! self::$confirm_data_structure) {
            $dataStructure = self::get_data_structure();
            $keys = [
                'automatic_payment_methods',
                'return_url',
                'payment_method',
                'receipt_email',
                'setup_future_usage',
                'mandate_data',
            ];
            $confirm = [];
            foreach ($keys as $key) {
                if (isset($dataStructure[$key])) {
                    $confirm[$key] = $dataStructure[$key];
                }
            }
            self::$confirm_data_structure = $confirm;
        }

        return self::$confirm_data_structure;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return static
     */
    public static function confirm(string $id, array $data)
    {
        $response = StripeClient::call(
            'POST',
            self::get_endpoint($id . '/confirm'),
            self::parse_data($data, false, self::get_confirm_data_structure())
        );

        return self::get(is_array($response) ? $response : []);
    }

    /**
     * @return static
     */
    public static function capture(string $id)
    {
        $response = StripeClient::call('POST', self::get_endpoint($id . '/capture'));

        return self::get(is_array($response) ? $response : []);
    }
}
