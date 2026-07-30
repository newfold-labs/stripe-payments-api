<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectDeleteTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectUpdateTrait;

/**
 * Stripe Product via the payments middleware.
 *
 * @see https://docs.stripe.com/api/products
 *
 * @property string|null   $id
 * @property string|null   $name
 * @property string|null   $description
 * @property string[]|null $images
 * @property array|null    $metadata
 * @property bool|null     $active
 * @property string|null   $default_price
 * @property array|null    $price
 * @property bool|null     $livemode
 * @property int|null      $created
 * @property int|null      $updated
 */
class Product extends ModelAbstract
{
    use ObjectCreateTrait;
    use ObjectReadTrait;
    use ObjectUpdateTrait;
    use ObjectDeleteTrait;

    protected static $endpoint = ':env/:brand/product';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Product ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'name' => [
                    'label' => 'Product name',
                    'type' => 'text',
                    'required' => true,
                    'default' => null,
                ],
                'description' => [
                    'label' => 'Product description',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'images' => [
                    'label' => 'Product images',
                    'type' => 'array',
                    'required' => false,
                    'default' => null,
                ],
                'metadata' => [
                    'label' => 'Product metadata',
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
                'default_price' => [
                    'label' => 'Default price ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'price' => [
                    'currency' => [
                        'label' => 'Price currency',
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
                        'label' => 'Price metadata',
                        'type' => 'hash',
                        'required' => false,
                        'default' => null,
                    ],
                    'active' => [
                        'label' => 'Price active',
                        'type' => 'bool',
                        'required' => false,
                        'default' => true,
                    ],
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * Archives (deactivates) a product. Stripe products cannot always be hard-deleted.
     *
     * @return static
     */
    public static function archive(string $id)
    {
        $response = StripeClient::call('DELETE', self::get_endpoint($id), []);

        return self::get(is_array($response) ? $response : ['id' => $id, 'active' => false]);
    }

    /**
     * Lists products for the connected account.
     *
     * @param array{limit?: int, starting_after?: string, ending_before?: string, active?: bool} $params
     *
     * @return static[]
     */
    public static function all(array $params = []): array
    {
        $response = StripeClient::call(
            'GET',
            StripeClient::getDefault()->formatEndpoint(':env/:brand/products'),
            self::parse_data($params, self::IGNORE_REQUIRED, self::get_pagination_data_structure())
        );

        if (! is_array($response)) {
            return [];
        }

        $items = $response['products'] ?? $response['data'] ?? [];

        return self::list(is_array($items) ? $items : []);
    }
}
