<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Models;

use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Traits\ObjectCreateTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectReadTrait;
use Bluehost\StripePaymentsAPI\Traits\ObjectUpdateTrait;

/**
 * Stripe Customer via the payments middleware.
 *
 * @property string|null $id
 * @property string|null $user_id
 * @property string|null $env
 * @property string|null $email
 * @property string|null $name
 * @property string|null $description
 * @property string|null $payment_method
 * @property string|null $phone
 * @property array|null  $address
 * @property array|null  $metadata
 * @property array|null  $invoice_settings
 */
class Customer extends ModelAbstract
{
    use ObjectReadTrait;
    use ObjectCreateTrait;
    use ObjectUpdateTrait;

    protected static $endpoint = ':env/:brand/customer';

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    public static function get_data_structure()
    {
        if (! self::$data_structure) {
            self::$data_structure = [
                'id' => [
                    'label' => 'Stripe customer ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'user_id' => [
                    'label' => 'User ID',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'env' => [
                    'label' => 'Customer environment',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'email' => [
                    'label' => 'Customer email',
                    'type' => 'email',
                    'required' => false,
                    'default' => null,
                ],
                'name' => [
                    'label' => 'Customer name',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'payment_method' => [
                    'label' => 'Payment method',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'phone' => [
                    'label' => 'Phone',
                    'type' => 'text',
                    'required' => false,
                    'default' => null,
                ],
                'address' => [
                    'label' => 'Address',
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
                'invoice_settings' => [
                    'label' => 'Invoice settings',
                    'type' => 'hash',
                    'required' => false,
                    'default' => null,
                ],
            ];
        }

        return self::$data_structure;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return PaymentMethod[]
     */
    public static function get_payment_methods(string $id, array $data = []): array
    {
        $paymentMethods = [];
        $nextIndex = null;
        $limit = $data['limit'] ?? null;
        $fetchAll = $limit === 0 || $limit === null;

        do {
            $pageData = array_merge($data, [
                'limit' => ($limit === 0 || $limit === null) ? 100 : $limit,
            ]);
            if ($nextIndex !== null) {
                $pageData['starting_after'] = $nextIndex;
            }

            $response = StripeClient::call(
                'GET',
                self::get_endpoint($id . '/payment-methods'),
                self::parse_data($pageData, false, self::get_pagination_data_structure())
            );

            if (! is_array($response)) {
                break;
            }

            $chunk = $response['payment_methods'] ?? $response['data'] ?? [];
            if (is_array($chunk)) {
                $paymentMethods = array_merge($paymentMethods, $chunk);
            }

            $metadata = $response['metadata'] ?? [];
            $hasMore = ! empty($metadata['has_more']);
            $nextIndex = $metadata['next_index'] ?? null;
        } while ($fetchAll && $hasMore && $nextIndex);

        return PaymentMethod::list($paymentMethods);
    }
}
