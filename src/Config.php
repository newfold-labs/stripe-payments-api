<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI;

/**
 * Immutable client configuration.
 */
final class Config
{
    public const ENVIRONMENT_LIVE = 'live';
    public const ENVIRONMENT_TEST = 'test';

    public const DEFAULT_PRODUCTION_URI = 'https://payments.yithemes.com/api';
    public const DEFAULT_STAGING_URI = 'https://staging-payments.yithemes.com/api';

    private string $baseUri;
    private string $environment;
    private string $brand;
    /** @var string|callable|null */
    private $authToken;
    private string $userAgent;
    private int $timeout;
    private bool $verifySsl;
    private bool $allowInsecureHttp;

    /**
     * @param string               $baseUri           Middleware base URL including `/api`.
     * @param string               $environment       `live` or `test`.
     * @param string               $brand             Brand slug registered on the middleware.
     * @param string|callable|null $authToken         Bearer token or callable that returns one.
     * @param string|null          $userAgent         Optional custom User-Agent.
     * @param int                  $timeout           Request timeout in seconds.
     * @param bool                 $verifySsl         Whether to verify TLS certificates.
     * @param bool                 $allowInsecureHttp Allows a plain `http://` $baseUri; only intended for local
     *                                                development. When false (default), a non-`https://` $baseUri
     *                                                is rejected so the bearer token/secrets are never sent
     *                                                in cleartext by accident.
     */
    public function __construct(
        string $baseUri = self::DEFAULT_PRODUCTION_URI,
        string $environment = self::ENVIRONMENT_LIVE,
        string $brand = '',
        $authToken = null,
        ?string $userAgent = null,
        int $timeout = 30,
        bool $verifySsl = true,
        bool $allowInsecureHttp = false
    ) {
        $baseUri = rtrim($baseUri, '/');
        if ($baseUri === '' || filter_var($baseUri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Config baseUri must be a valid URL.');
        }

        $scheme = strtolower((string) (parse_url($baseUri, PHP_URL_SCHEME) ?? ''));
        if ($scheme !== 'https' && ! ($allowInsecureHttp && $scheme === 'http')) {
            throw new \InvalidArgumentException(
                'Config baseUri must use https:// (pass allowInsecureHttp: true only for local development).'
            );
        }

        if (! in_array($environment, [self::ENVIRONMENT_LIVE, self::ENVIRONMENT_TEST], true)) {
            throw new \InvalidArgumentException('Config environment must be "live" or "test".');
        }

        if ($brand === '') {
            throw new \InvalidArgumentException('Config brand is required.');
        }

        if ($timeout < 1) {
            throw new \InvalidArgumentException('Config timeout must be a positive integer.');
        }

        $this->baseUri = $baseUri;
        $this->environment = $environment;
        $this->brand = $brand;
        $this->authToken = $authToken;
        $this->userAgent = $userAgent ?? 'Bluehost\\StripePaymentsAPI/1.0';
        $this->timeout = $timeout;
        $this->verifySsl = $verifySsl;
        $this->allowInsecureHttp = $allowInsecureHttp;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function shouldVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    /**
     * Resolves the current bearer token, if any.
     */
    public function resolveAuthToken(): ?string
    {
        if ($this->authToken === null || $this->authToken === false || $this->authToken === '') {
            return null;
        }

        if (is_callable($this->authToken)) {
            $token = ($this->authToken)();
            return is_string($token) && $token !== '' ? $token : null;
        }

        return is_string($this->authToken) ? $this->authToken : null;
    }

    /**
     * @param string|callable|null $authToken
     */
    public function withAuthToken($authToken): self
    {
        return new self(
            $this->baseUri,
            $this->environment,
            $this->brand,
            $authToken,
            $this->userAgent,
            $this->timeout,
            $this->verifySsl,
            $this->allowInsecureHttp
        );
    }
}
