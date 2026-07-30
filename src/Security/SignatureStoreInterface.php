<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Security;

/**
 * Stores outbound request signatures so a consumer can verify callback challenges.
 *
 * In WordPress, implement this with transients. The default in-memory store is
 * only reliable within a single process lifetime.
 */
interface SignatureStoreInterface
{
    /**
     * Persist a signature for later verification.
     *
     * @param string $signature Signature value (typically a UUID).
     * @param int    $ttl       Time-to-live in seconds.
     */
    public function put(string $signature, int $ttl): void;

    /**
     * Returns the most recently stored signature, or null if missing/expired.
     */
    public function getLast(): ?string;

    /**
     * Returns true when the given signature is known and still valid.
     */
    public function verify(string $signature): bool;
}
