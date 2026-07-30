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
     * @return static
     */
    public static function read(string $id = '')
    {
        $response = StripeClient::call('GET', self::get_endpoint($id), []);

        return self::get(is_array($response) ? $response : []);
    }
}
