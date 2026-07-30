<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Exceptions;

use Bluehost\StripePaymentsAPI\Abstracts\ExceptionAbstract;

/**
 * Raised when client-side schema validation fails before a network call.
 */
final class ValidationException extends ExceptionAbstract
{
    public function __construct(string $message, array $details = [])
    {
        parent::__construct($message, 0, 'validation_error', $details);
    }
}
