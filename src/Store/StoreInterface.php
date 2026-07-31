<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Store;

/**
 * Generic key/value persistence used by the client for anything it needs to
 * remember between requests: request signatures, the current connected
 * account's bearer token, etc.
 *
 * In WordPress, implement this with transients/options. The default in-memory
 * store is only reliable within a single process lifetime.
 */
interface StoreInterface
{
    /**
     * Returns the stored value for $key, or null if missing/expired.
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * Persists $value under $key.
     *
     * @param mixed $value
     * @param int   $ttl   Time-to-live in seconds. 0 means "no expiration".
     */
    public function set(string $key, $value, int $ttl = 0): void;

    /**
     * Removes any value stored under $key.
     */
    public function delete(string $key): void;
}
