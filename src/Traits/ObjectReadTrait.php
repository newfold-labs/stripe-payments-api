<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Traits;

use Bluehost\StripePaymentsAPI\Client\StripeClient;

/**
 * Adds read() via GET to the model endpoint.
 */
trait ObjectReadTrait
{
    /**
     * @param array<int, string> $expand Stripe fields to expand in the response.
     *
     * @return static
     */
    public static function read(string $id = '', array $expand = [])
    {
        $response = StripeClient::call(
            'GET',
            self::get_endpoint($id),
            $expand === [] ? [] : ['expand' => array_values($expand)]
        );

        return self::get(is_array($response) ? $response : []);
    }
}
