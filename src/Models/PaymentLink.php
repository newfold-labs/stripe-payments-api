<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectUpdateTrait;

/**
 * Stripe Payment Link via the payments middleware.
 *
 * @see https://docs.stripe.com/api/payment-link
 *
 * @property string|null $id
 * @property string|null $url
 * @property bool|null   $active
 * @property array|null  $line_items
 * @property array|null  $metadata
 * @property array|null  $after_completion
 * @property bool|null   $allow_promotion_codes
 * @property bool|null   $livemode
 */
class PaymentLink extends ModelAbstract
{
    use ObjectCreateTrait;
    use ObjectReadTrait;
    use ObjectUpdateTrait;

    protected static $endpoint = ':env/:brand/payment-link';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Payment Link ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'url' => [
                    'label' => 'Payment Link URL',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                    'validation' => 'url',
                ],
                'line_items' => [
                    'label' => 'Line items',
                    'type' => 'array',
                    'required' => true,
                    'default' => null,
                    'minItems' => 1,
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
                'after_completion' => [
                    'type' => [
                        'label' => 'After completion type',
                        'type' => 'text',
                        'required' => true,
                        'default' => null,
                    ],
                    'redirect' => [
                        'url' => [
                            'label' => 'Redirect URL',
                            'type' => 'text',
                            'required' => false,
                            'default' => null,
                            'validation' => 'url',
                        ],
                    ],
                ],
                'allow_promotion_codes' => [
                    'label' => 'Allow promotion codes',
                    'type' => 'bool',
                    'required' => false,
                    'default' => false,
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * Deactivates a payment link (Stripe payment links cannot be deleted).
     *
     * @return static
     */
    public static function deactivate(string $id)
    {
        return self::update($id, ['active' => false]);
    }

    /**
     * Lists payment links for the connected account.
     *
     * @param array{limit?: int, starting_after?: string, ending_before?: string, active?: bool} $params
     *
     * @return static[]
     */
    public static function all(array $params = []): array
    {
        $response = StripeClient::call(
            'GET',
            StripeClient::getDefault()->formatEndpoint(':env/:brand/payment-links'),
            self::parse_data($params, self::IGNORE_REQUIRED, self::get_pagination_data_structure())
        );

        if (! is_array($response)) {
            return [];
        }

        $items = $response['payment_links'] ?? $response['data'] ?? [];

        return self::list(is_array($items) ? $items : []);
    }

    /**
     * Replaces line items on an existing payment link.
     *
     * @param array<int, array{price?: string, quantity?: int, adjustable_quantity?: array<string, mixed>, id?: string, deleted?: bool}> $lineItems
     *
     * @return static
     */
    public static function update_line_items(string $id, array $lineItems)
    {
        $validated = self::parse_data(
            ['line_items' => $lineItems],
            false,
            [
                'line_items' => [
                    'label' => 'Line items',
                    'type' => 'array',
                    'required' => true,
                    'default' => null,
                    'minItems' => 1,
                ],
            ]
        );

        $response = StripeClient::call(
            'POST',
            self::get_endpoint($id . '/line-items'),
            ['line_items' => $validated['line_items']]
        );

        return self::get(is_array($response) ? $response : []);
    }
}
