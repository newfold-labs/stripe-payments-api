<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Store;

/**
 * Process-local store. Suitable for CLI/tests; supply a durable
 * implementation (e.g. WP transients) in long-lived web apps.
 */
final class InMemoryStore implements StoreInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    /** @var array<string, int> */
    private array $expiresAt = [];

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        if (! array_key_exists($key, $this->values)) {
            return null;
        }

        if (isset($this->expiresAt[$key]) && time() > $this->expiresAt[$key]) {
            $this->delete($key);
            return null;
        }

        return $this->values[$key];
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, int $ttl = 0): void
    {
        $this->values[$key] = $value;

        if ($ttl > 0) {
            $this->expiresAt[$key] = time() + $ttl;
        } else {
            unset($this->expiresAt[$key]);
        }
    }

    public function delete(string $key): void
    {
        unset($this->values[$key], $this->expiresAt[$key]);
    }
}
