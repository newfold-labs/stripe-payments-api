<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;

/**
 * Stripe Price nested under a Product on the payments middleware.
 *
 * @see https://docs.stripe.com/api/prices
 *
 * @property string|null $id
 * @property string|null $product
 * @property string|null $currency
 * @property int|null    $unit_amount
 * @property array|null  $recurring
 * @property array|null  $metadata
 * @property bool|null   $active
 * @property bool|null   $livemode
 */
class Price extends ModelAbstract
{
    protected static $endpoint = ':env/:brand/product';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Price ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'currency' => [
                    'label' => 'Currency',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                    'validation' => 'currency',
                ],
                'unit_amount' => [
                    'label' => 'Unit amount',
                    'type' => 'number',
                    'required' => true,
                    'default' => null,
                    'min' => 0,
                ],
                'recurring' => [
                    'interval' => [
                        'label' => 'Billing interval',
                        'type' => 'text',
                        'required' => true,
                        'default' => null,
                    ],
                    'interval_count' => [
                        'label' => 'Interval count',
                        'type' => 'number',
                        'required' => false,
                        'default' => 1,
                        'min' => 1,
                    ],
                ],
                'metadata' => [
                    'label' => 'Metadata',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
                'active' => [
                    'label' => 'Active',
                    'type' => 'bool',
                    'required' => false,
                    'default' => true,
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * Creates a price for the given product.
     *
     * @param array<string, mixed> $data
     *
     * @return static
     */
    public static function create_for_product(string $productId, array $data)
    {
        $response = StripeClient::call(
            'POST',
            self::get_endpoint($productId . '/price'),
            self::get_data($data)
        );

        return self::get(is_array($response) ? $response : []);
    }

    /**
     * Lists prices for a product.
     *
     * @param array{limit?: int, starting_after?: string, ending_before?: string, active?: bool} $params
     *
     * @return static[]
     */
    public static function all_for_product(string $productId, array $params = []): array
    {
        $response = StripeClient::call(
            'GET',
            self::get_endpoint($productId . '/prices'),
            self::parse_data($params, self::IGNORE_REQUIRED, self::get_pagination_data_structure())
        );

        if (! is_array($response)) {
            return [];
        }

        $items = $response['prices'] ?? $response['data'] ?? [];

        return self::list(is_array($items) ? $items : []);
    }
}
