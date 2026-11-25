<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;

/**
 * Stripe Checkout Session via the payments middleware.
 *
 * @see https://docs.stripe.com/api/checkout/sessions
 *
 * @property string|null             $id
 * @property string|null             $mode
 * @property array|null              $line_items
 * @property string|null             $customer
 * @property string|null             $success_url
 * @property string|null             $cancel_url
 * @property array|null              $metadata
 * @property array|null              $subscription_data
 * @property string|null             $billing_address_collection `auto` or `required`
 * @property array|null              $phone_number_collection    `['enabled' => bool]`
 * @property array|null              $shipping_address_collection `['allowed_countries' => ['IT', 'US']]`
 * @property array|null              $customer_update            `address`, `name`, `shipping`: `auto` or `never`; needs `customer`
 * @property string|null             $status
 * @property string|null             $payment_status
 * @property string|null             $url
 * @property string|array|null       $subscription
 */
class CheckoutSession extends ModelAbstract
{
    use ObjectCreateTrait;
    use ObjectReadTrait;

    protected static $endpoint = ':env/:brand/checkout-session';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Checkout Session ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'mode' => [
                    'label' => 'Checkout mode',
                    'type' => 'select',
                    'required' => true,
                    'default' => null,
                    'options' => [
                        'payment' => 'payment',
                        'subscription' => 'subscription',
                    ],
                ],
                'line_items' => [
                    'label' => 'Line items',
                    'type' => 'array',
                    'required' => true,
                    'default' => null,
                    'minItems' => 1,
                ],
                'customer' => [
                    'label' => 'Customer ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'success_url' => [
                    'label' => 'Success URL',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'url',
                ],
                'cancel_url' => [
                    'label' => 'Cancel URL',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'url',
                ],
                'metadata' => [
                    'label' => 'Metadata',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'subscription_data' => [
                    'label' => 'Subscription data',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'billing_address_collection' => [
                    'label' => 'Billing address collection',
                    'type' => 'select',
                    'required' => false,
                    'default' => null,
                    'options' => [
                        'auto' => 'auto',
                        'required' => 'required',
                    ],
                ],
                'phone_number_collection' => [
                    'enabled' => [
                        'label' => 'Phone number collection enabled',
                        'type' => 'bool',
                        // Not `required`: the validator reads a `false` as missing.
                        'required' => false,
                        'default' => null,
                    ],
                ],
                'shipping_address_collection' => [
                    'allowed_countries' => [
                        'label' => 'Shipping allowed countries',
                        'type' => 'array',
                        'required' => true,
                        'default' => null,
                        'minItems' => 1,
                    ],
                ],
                'customer_update' => [
                    'address' => [
                        'label' => 'Save billing address on customer',
                        'type' => 'select',
                        'required' => false,
                        'default' => null,
                        'options' => ['auto' => 'auto', 'never' => 'never'],
                    ],
                    'name' => [
                        'label' => 'Save name on customer',
                        'type' => 'select',
                        'required' => false,
                        'default' => null,
                        'options' => ['auto' => 'auto', 'never' => 'never'],
                    ],
                    'shipping' => [
                        'label' => 'Save shipping on customer',
                        'type' => 'select',
                        'required' => false,
                        'default' => null,
                        'options' => ['auto' => 'auto', 'never' => 'never'],
                    ],
                ],
            ];
        }

        return self::$data_structure;
    }
}
