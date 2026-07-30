<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;

/**
 * Stripe PaymentMethod via the payments middleware.
 *
 * @property string|null $id
 * @property string|null $type
 * @property string|null $customer
 * @property string|null $allow_redisplay
 * @property array|null  $metadata
 * @property array|null  $card
 * @property array|null  $klarna
 */
class PaymentMethod extends ModelAbstract
{
    use ObjectReadTrait;

    protected static $endpoint = ':env/:brand/payment-method';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    /** @var array<string, mixed>|null */
    protected static $attach_data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Payment Method ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'type' => [
                    'label' => 'Payment Method type',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'allow_redisplay' => [
                    'label' => 'Whether method can be shown as saved method in the checkout flow',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'customer' => [
                    'label' => 'Customer to whom the method is attached',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'metadata' => [
                    'label' => 'Payment Method metadata',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'card' => [
                    'label' => 'Card details',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'klarna' => [
                    'label' => 'Klarna details',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_attach_data_structure(): array
    {
        if (! self::$attach_data_structure) {
            self::$attach_data_structure = [
                'customer' => [
                    'label' => 'Customer ID',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                ],
            ];
        }

        return self::$attach_data_structure;
    }

    /**
     * @return static
     */
    public static function attach(string $id, string $customerId)
    {
        $response = StripeClient::call(
            'POST',
            self::get_endpoint($id . '/attach'),
            self::parse_data(['customer' => $customerId], false, self::get_attach_data_structure())
        );

        return self::get(is_array($response) ? $response : []);
    }

    /**
     * @return static
     */
    public static function detach(string $id)
    {
        $response = StripeClient::call('POST', self::get_endpoint($id . '/detach'));

        return self::get(is_array($response) ? $response : []);
    }
}
