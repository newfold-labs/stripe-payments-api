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
            ];
        }

        return self::$data_structure;
    }
}
