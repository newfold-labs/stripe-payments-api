<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Exceptions;

use Bluehost\StripePaymentsAPI\Abstracts\ExceptionAbstract;

/**
 * Raised when the HTTP transport fails to reach the middleware.
 */
final class ProcessorException extends ExceptionAbstract
{
    public function __construct(string $message)
    {
        parent::__construct($message, 0, 'connection_error');
    }
}
