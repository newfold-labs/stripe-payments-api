<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;

/**
 * Stripe Billing Portal Session via the payments middleware.
 *
 * @see https://docs.stripe.com/api/customer_portal/sessions
 *
 * @property string|null $id
 * @property string|null $customer
 * @property string|null $return_url
 * @property string|null $url
 */
class BillingPortalSession extends ModelAbstract
{
    use ObjectCreateTrait;

    protected static $endpoint = ':env/:brand/billing-portal-session';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Billing Portal Session ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'customer' => [
                    'label' => 'Customer ID',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                ],
                'return_url' => [
                    'label' => 'Return URL',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'url',
                ],
                'url' => [
                    'label' => 'Billing Portal URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'url',
                ],
            ];
        }

        return self::$data_structure;
    }
}
