<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;

/**
 * Stripe Subscription via the payments middleware.
 *
 * Subscriptions are created through Checkout Sessions. The middleware currently
 * exposes retrieval only.
 *
 * @see https://docs.stripe.com/api/subscriptions
 *
 * @property string|null $id
 * @property string|null $customer
 * @property string|null $status
 * @property array|null  $items
 * @property array|null  $metadata
 */
class Subscription extends ModelAbstract
{
    use ObjectReadTrait;

    protected static $endpoint = ':env/:brand/subscription';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Subscription ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'customer' => [
                    'label' => 'Customer ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'status' => [
                    'label' => 'Subscription status',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'items' => [
                    'label' => 'Subscription items',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'metadata' => [
                    'label' => 'Metadata',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
            ];
        }

        return self::$data_structure;
    }
}
