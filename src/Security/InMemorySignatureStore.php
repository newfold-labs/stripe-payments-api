<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Security;

/**
 * Process-local signature store. Suitable for CLI/tests; supply a durable
 * implementation (e.g. WP transients) in long-lived web apps.
 */
final class InMemorySignatureStore implements SignatureStoreInterface
{
    private ?string $signature = null;
    private int $expiresAt = 0;

    public function put(string $signature, int $ttl): void
    {
        $this->signature = $signature;
        $this->expiresAt = time() + max(1, $ttl);
    }

    public function getLast(): ?string
    {
        if ($this->signature === null || time() > $this->expiresAt) {
            $this->signature = null;
            return null;
        }

        return $this->signature;
    }

    public function verify(string $signature): bool
    {
        $last = $this->getLast();

        return $last !== null && hash_equals($last, $signature);
    }
}
