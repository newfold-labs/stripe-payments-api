<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Traits;

use Bluehost\StripePaymentsAPI\Client\StripeClient;

/**
 * Adds create() via POST to the model endpoint.
 */
trait ObjectCreateTrait
{
    /**
     * @param array<string, mixed> $data
     *
     * @return static
     */
    public static function create(array $data)
    {
        $response = StripeClient::call('POST', self::get_endpoint(), self::get_data($data));

        return self::get(is_array($response) ? $response : []);
    }
}
