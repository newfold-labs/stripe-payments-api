<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Traits;

use Bluehost\StripePaymentsAPI\Client\StripeClient;

/**
 * Adds delete() via DELETE to the model endpoint.
 */
trait ObjectDeleteTrait
{
    public static function delete(string $id): bool
    {
        StripeClient::call('DELETE', self::get_endpoint($id), []);

        return true;
    }
}
