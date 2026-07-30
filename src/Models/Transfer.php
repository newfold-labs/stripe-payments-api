<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectUpdateTrait;

/**
 * Stripe Transfer via the payments middleware.
 *
 * @property string|null $id
 * @property int|null    $amount
 * @property string|null $currency
 * @property string|null $destination
 * @property string|null $description
 * @property array|null  $metadata
 * @property string|null $transfer_group
 * @property int|null    $amount_reversed
 * @property string|null $destination_payment
 */
class Transfer extends ModelAbstract
{
    use ObjectReadTrait;
    use ObjectCreateTrait;
    use ObjectUpdateTrait;

    protected static $endpoint = ':env/:brand/transfers';

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
                'id' => [
                    'label' => 'Transfer ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'amount' => [
                    'label' => 'Transfer amount',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0,
                ],
                'currency' => [
                    'label' => 'Transfer currency',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                    'validation' => 'currency',
                ],
                'destination' => [
                    'label' => 'Destination account',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'description' => [
                    'label' => 'Transfer description',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                'metadata' => [
                    'label' => 'Transfer metadata',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'transfer_group' => [
                    'label' => 'Transfer group',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'amount_reversed' => [
                    'label' => 'Amount reversed',
                    'type' => 'number',
                    'required' => false,
                    'default' => null,
                ],
                'destination_payment' => [
                    'label' => 'Destination payment',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
            ];
        }

        return self::$data_structure;
    }
}
