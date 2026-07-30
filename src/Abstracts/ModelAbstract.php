<?php

declare(strict_types=1);

namespace Bluehost\StripePaymentsAPI\Abstracts;

use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Exceptions\ValidationException;
use Bluehost\StripePaymentsAPI\Security\Uuid;

/**
 * Base model with schema-driven sanitization/validation.
 *
 * Unknown fields are passed through unchanged (not an allowlist), matching the
 * original Client behaviour for forward-compatible middleware fields.
 */
abstract class ModelAbstract
{
    public const IGNORE_REQUIRED = 1;
    public const SANITIZE_ONLY = 2;

    /** @var array<string, mixed> */
    protected array $_values = [];

    /** @var array<string, mixed>|null */
    protected static $data_structure;

    /** @var string */
    protected static $endpoint = '';

    /**
     * @param array<string, mixed> $raw
     *
     * @throws ValidationException
     */
    public function __construct(array $raw = [])
    {
        // When hydrating API responses, ignore required create-time constraints.
        $raw = static::get_data($raw, self::IGNORE_REQUIRED | self::SANITIZE_ONLY);
        foreach ($raw as $fieldId => $fieldValue) {
            $this->__set((string) $fieldId, $fieldValue);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->_values[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value): void
    {
        $this->_values[$key] = $value;
    }

    public function __isset($key): bool
    {
        return isset($this->_values[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->_values;
    }

    public static function get_endpoint(string $subpath = ''): string
    {
        $formatted = StripeClient::getDefault()->formatEndpoint(static::$endpoint);
        $subpath = static::sanitize_path_segment($subpath);

        $parts = array_filter(
            [$formatted, $subpath],
            static function ($part) {
                return $part !== null && $part !== '';
            }
        );

        return implode('/', $parts);
    }

    /**
     * Validates and percent-encodes a (possibly multi-segment) path fragment appended to
     * a model's base endpoint, e.g. `"{$id}/attach"`.
     *
     * Without this, a caller-supplied ID containing `../` (or a `//`-producing empty
     * segment) would be resolved by the underlying HTTP client's RFC 3986 relative-URI
     * resolution, letting the request escape the intended `:env/:brand/resource` path
     * onto an arbitrary path on the same host. Encoding each segment independently keeps
     * well-formed IDs (Stripe-style `cus_…`, `pm_…`, …) byte-for-byte unchanged.
     *
     * @throws ValidationException When $subpath contains an empty, `.`, or `..` segment.
     */
    protected static function sanitize_path_segment(string $subpath): string
    {
        if ($subpath === '') {
            return '';
        }

        $segments = explode('/', $subpath);
        $safe = [];

        foreach ($segments as $segment) {
            $decoded = rawurldecode($segment);

            if ($segment === '' || $decoded === '.' || $decoded === '..') {
                throw new ValidationException(
                    sprintf('Invalid path segment "%s" in endpoint ID.', $segment)
                );
            }

            $safe[] = rawurlencode($segment);
        }

        return implode('/', $safe);
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_data_structure()
    {
        return static::$data_structure ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function get_pagination_data_structure(): array
    {
        return [
            'limit' => [
                'label' => 'Maximum number of items returned by the request',
                'type' => 'number',
                'required' => false,
                'default' => 100,
                'min' => 1,
                'max' => 100,
            ],
            'starting_after' => [
                'label' => 'Starting index',
                'type' => 'text',
                'required' => false,
                'default' => null,
            ],
            'ending_before' => [
                'label' => 'Ending index',
                'type' => 'text',
                'required' => false,
                'default' => null,
            ],
            'active' => [
                'label' => 'Active filter',
                'type' => 'bool',
                'required' => false,
                'default' => null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param int|false            $flags
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public static function get_data($data, $flags = false)
    {
        return static::parse_data($data, $flags);
    }

    /**
     * @param array<string, mixed>      $data
     * @param int|false                 $flags
     * @param array<string, mixed>|false $structure
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected static function parse_data($data, $flags = false, $structure = false)
    {
        $validated = [];

        if (! is_array($data)) {
            $data = [];
        }

        if (! $structure) {
            $structure = static::get_data_structure();
        }

        if (! is_array($structure) || $structure === []) {
            return $data;
        }

        $ignoreRequired = $flags !== false && ($flags & self::IGNORE_REQUIRED) === self::IGNORE_REQUIRED;
        $sanitizeOnly = $flags !== false && ($flags & self::SANITIZE_ONLY) === self::SANITIZE_ONLY;

        $data = static::maybe_convert_case($data);

        foreach ($structure as $settingId => $setting) {
            $value = array_key_exists($settingId, $data) ? $data[$settingId] : null;

            $hasSubSettings = is_array($setting) && $setting !== [] && is_array(current($setting))
                && ! array_key_exists('type', $setting);

            if ($hasSubSettings) {
                // Skip optional nested objects when the parent key was not provided.
                if ($value === null || $value === false || $value === '' || $value === []) {
                    continue;
                }
                if (! is_array($value)) {
                    if (! $sanitizeOnly) {
                        throw new ValidationException(sprintf('%s must be an object', (string) $settingId));
                    }
                    continue;
                }
                $parsedSub = static::parse_data($value, $flags, $setting);
                if ($parsedSub !== []) {
                    $validated[$settingId] = $parsedSub;
                }
                continue;
            }

            $required = ! empty($setting['required']);
            $default = $setting['default'] ?? null;
            $type = $setting['type'] ?? 'text';
            $options = $setting['options'] ?? [];
            $deps = $setting['deps'] ?? null;
            $label = $setting['label'] ?? (string) $settingId;
            $min = $setting['min'] ?? false;
            $max = $setting['max'] ?? false;

            if (in_array($type, ['checkbox', 'onoff', 'bool'], true)) {
                if ($value === null && ! array_key_exists($settingId, $data)) {
                    // Do not inject defaults on partial updates.
                    $value = $ignoreRequired ? null : $default;
                } elseif ($value !== null) {
                    $value = in_array($value, ['yes', 'on', 'true', '1', true, 1], true);
                }
            } elseif (in_array($type, ['select', 'radio'], true) && $options !== [] && $value !== null && $value !== false && $value !== '') {
                if (! array_key_exists($value, $options) && ! in_array($value, $options, true)) {
                    $value = false;
                    if (! $sanitizeOnly) {
                        throw new ValidationException(sprintf('Please choose a valid option for %s', $label));
                    }
                }
            } elseif ($type === 'email' && $value) {
                $value = filter_var((string) $value, FILTER_VALIDATE_EMAIL);
                if ($value === false && ! $sanitizeOnly) {
                    throw new ValidationException(sprintf('Please provide a valid email address for %s', $label));
                }
            } elseif ($type === 'number' && $value !== null && $value !== false && $value !== '') {
                if (! is_numeric($value) || ($min !== false && $value < $min) || ($max !== false && $value > $max)) {
                    $value = false;
                    if (! $sanitizeOnly) {
                        throw new ValidationException(sprintf('Please provide a valid value for %s', $label));
                    }
                } else {
                    $value = strpos((string) $value, '.') !== false ? (float) $value : (int) $value;
                }
            } elseif ($type === 'textarea' && $value) {
                $value = trim(strip_tags((string) $value));
            } elseif ($type === 'hash' && is_array($value)) {
                $value = static::sanitizeHash($value);
            } elseif ($type === 'array' && $value !== null) {
                if (! is_array($value)) {
                    if (! $sanitizeOnly) {
                        throw new ValidationException(sprintf('%s must be an array', $label));
                    }
                    $value = null;
                } elseif (! empty($setting['minItems']) && count($value) < (int) $setting['minItems']) {
                    if (! $sanitizeOnly) {
                        throw new ValidationException(
                            sprintf('%s must contain at least %d item(s)', $label, (int) $setting['minItems'])
                        );
                    }
                }
            } elseif ($value !== null && $value !== false && $value !== '' && ! is_array($value)) {
                $value = is_string($value) ? trim($value) : $value;
            } elseif (($value === null || $value === false || $value === '') && $default !== null && $default !== false) {
                // Apply defaults only when creating (not when IGNORE_REQUIRED / update-style).
                if (! $ignoreRequired) {
                    $value = $default;
                } else {
                    $value = null;
                }
            } else {
                $value = ($value === false || $value === '') ? null : $value;
            }

            if ($value && isset($setting['validation'])) {
                $value = static::applyValidation($setting['validation'], $value, $label, $sanitizeOnly);
            }

            // Fixed dependency check (original Client used undefined $posted / wrong keys).
            if (! empty($deps) && is_array($deps)) {
                $depId = $deps['id'] ?? null;
                $depValue = $deps['value'] ?? null;
                if ($depId !== null) {
                    $actual = $data[$depId] ?? null;
                    $required = $required && in_array($actual, (array) $depValue, true);
                }
            }

            if ($required && ($value === null || $value === false || $value === '') && ! $ignoreRequired && ! $sanitizeOnly) {
                throw new ValidationException(sprintf('%s is a required field', $label));
            }

            if ($value !== null) {
                $validated[$settingId] = $value;
            }
        }

        // Pass through undeclared fields so new middleware/Stripe keys are never dropped.
        foreach ($data as $key => $extraValue) {
            if (! array_key_exists($key, $structure) && ! array_key_exists($key, $validated)) {
                $validated[$key] = $extraValue;
            }
        }

        return $validated;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws ValidationException
     */
    protected static function applyValidation(string $validation, $value, string $label, bool $sanitizeOnly)
    {
        switch ($validation) {
            case 'email':
                $filtered = filter_var((string) $value, FILTER_VALIDATE_EMAIL);
                if ($filtered === false && ! $sanitizeOnly) {
                    throw new ValidationException(sprintf('Please enter a valid email address for %s', $label));
                }
                return $filtered !== false ? $filtered : $value;

            case 'uuid':
                if (! Uuid::isValid((string) $value) && ! $sanitizeOnly) {
                    throw new ValidationException(sprintf('Please enter a valid UUID for %s', $label));
                }
                return $value;

            case 'url':
                $filtered = filter_var((string) $value, FILTER_VALIDATE_URL);
                if ($filtered === false && ! $sanitizeOnly) {
                    throw new ValidationException(sprintf('Please enter a valid URL for %s', $label));
                }
                return $filtered !== false ? $filtered : $value;

            case 'country':
                $normalized = strtoupper((string) $value);
                if (! preg_match('/^[A-Z]{2}$/', $normalized) && ! $sanitizeOnly) {
                    throw new ValidationException(sprintf('Please enter a valid country code for %s', $label));
                }
                return $normalized;

            case 'currency':
                $normalized = strtolower((string) $value);
                if (! preg_match('/^[a-z]{3}$/', $normalized) && ! $sanitizeOnly) {
                    throw new ValidationException(sprintf('Please enter a valid currency code for %s', $label));
                }
                return $normalized;

            default:
                return $value;
        }
    }

    /**
     * @param array<string|int, mixed> $value
     *
     * @return array<string|int, mixed>
     */
    protected static function sanitizeHash(array $value): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $result[$key] = static::sanitizeHash($item);
            } elseif (is_string($item)) {
                $result[$key] = trim(strip_tags($item));
            } else {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return static
     *
     * @throws ValidationException
     */
    protected static function get(array $raw = [])
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        return new static($raw);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return static[]
     */
    public static function list(array $items = []): array
    {
        $set = [];

        foreach ($items as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            try {
                $set[] = static::get($raw);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $set;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected static function maybe_convert_case($data)
    {
        if (! is_array($data) || $data === []) {
            return is_array($data) ? $data : [];
        }

        $formatted = [];
        foreach ($data as $field => $value) {
            $field = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $field));
            $formatted[$field] = $value;
        }

        return $formatted;
    }
}
