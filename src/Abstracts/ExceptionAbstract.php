<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Abstracts;

/**
 * Base exception with synthetic type and details payload.
 */
abstract class ExceptionAbstract extends \Exception
{
    protected string $synthetic = '';

    /** @var array<string, mixed> */
    protected array $details = [];

    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message, int $code = 0, string $synthetic = '', array $details = [])
    {
        $this->synthetic = $synthetic;
        $this->details = $details;

        parent::__construct($message, $code);
    }

    public function getType(): string
    {
        return $this->synthetic;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
