<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Exceptions;

use Bluehost\StripePaymentsAPI\Abstracts\ExceptionAbstract;

/**
 * Raised when the middleware returns a non-success HTTP status.
 */
final class ApiException extends ExceptionAbstract
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message, int $httpCode, string $synthetic = '', array $details = [])
    {
        $details = array_merge(
            [
                'path' => '',
                'method' => '',
                'payload' => false,
                'response' => false,
                'severity' => false,
                'errors' => [],
                'data' => [],
            ],
            $details
        );

        parent::__construct($message, $httpCode, $synthetic, $details);
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->details['path'] ?? false;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->details['method'] ?? false;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->details['payload'] ?? false;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->details['response'] ?? false;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->details['errors'] ?? false;
    }
}
