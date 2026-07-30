<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Traits;

use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Abstracts\ModelAbstract;

/**
 * Adds update() via PUT to the model endpoint.
 */
trait ObjectUpdateTrait
{
    /**
     * @param array<string, mixed> $data
     *
     * @return static
     */
    public static function update(string $id, array $data)
    {
        $response = StripeClient::call(
            'PUT',
            self::get_endpoint($id),
            self::get_data($data, ModelAbstract::IGNORE_REQUIRED)
        );

        return self::get(is_array($response) ? $response : []);
    }
}
