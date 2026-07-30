# newfold-labs/stripe-payments-api

PHP 8.0+ client for the **YITH Stripe Payments middleware** — the proxy used by Bluehost/Newfold payment plugins.

It talks to the middleware (`payments.yithemes.com` / staging), not to `api.stripe.com` directly. Request and response shapes follow [Stripe's API](https://docs.stripe.com/api) where the middleware passes them through.

## Requirements

- PHP 8.0+
- `ext-json`
- Guzzle 7

## Installation

```bash
"repositories": {
    "newfold": {
      "type": "composer",
      "url": "https://newfold-labs.github.io/satis/",
      "only": [
        "newfold-labs/*"
      ]
    }
  },
    "require": {
    "newfold-labs/stripe-payments-api": "^1.0.0"
  },
```

## Quick start

```php
use Bluehost\StripePaymentsAPI\Config;
use Bluehost\StripePaymentsAPI\Client\StripeClient;
use Bluehost\StripePaymentsAPI\Models\Customer;
use Bluehost\StripePaymentsAPI\Models\Product;
use Bluehost\StripePaymentsAPI\Models\PaymentLink;

$client = new StripeClient(new Config(
    baseUri: 'https://payments.yithemes.com/api',
    environment: Config::ENVIRONMENT_LIVE, // or ENVIRONMENT_TEST
    brand: 'bluehost',
    authToken: fn () => $app->getBearerToken(), // string or callable
));

$customer = Customer::create([
    'email' => 'jane@example.com',
    'name' => 'Jane Doe',
]);

$product = Product::create([
    'name' => 'Pro Plan',
    'price' => [
        'currency' => 'usd',
        'unit_amount' => 4900,
        'recurring' => ['interval' => 'month'],
    ],
]);

$link = PaymentLink::create([
    'line_items' => [
        ['price' => $product->default_price, 'quantity' => 1],
    ],
]);

echo $link->url;
```

Constructing `StripeClient` also registers it as the process-wide default used by static model methods (`Product::create()`, etc.).

## Resources

| Model | Endpoint(s) | Methods |
|-------|-------------|---------|
| `Account` | `:env/:brand/account` | `create`, `read`, `delete` |
| `Token` | `:env/:brand/token` | `create` |
| `PublicKey` | `:env/:brand/public-key` | `retrieve()` |
| `AuthUrl` | `:env/:brand/connect/auth-url` | `create` (GET) |
| `AuthToken` | `:env/:brand/connect/token` | `create` |
| `Customer` | `:env/:brand/customer` | `create`, `read`, `update`, `get_payment_methods` |
| `Intent` | `:env/:brand/intent` | `create`, `read`, `update`, `confirm`, `capture` |
| `PaymentMethod` | `:env/:brand/payment-method` | `read`, `attach`, `detach` |
| `Transfer` | `:env/:brand/transfers` | `create`, `read`, `update` |
| `Product` | `:env/:brand/product(s)` | `create`, `read`, `update`, `delete`/`archive`, `all` |
| `Price` | `:env/:brand/product/:id/price(s)` | `create_for_product`, `all_for_product` |
| `PaymentLink` | `:env/:brand/payment-link(s)` | `create`, `read`, `update`, `deactivate`, `all`, `update_line_items` |


## Request signatures

Every outbound call sends `X-Request-Signature` (UUID v4) and stores it in the configured `SignatureStoreInterface` for 10 minutes. Supply your own store (e.g. WordPress transients) when the middleware must challenge your site asynchronously:

```php
use Bluehost\StripePaymentsAPI\Security\SignatureStoreInterface;

$client = new StripeClient($config, null, $mySignatureStore);
```

## Development

```bash
composer install
composer test
composer cs
```

## License

GPL-2.0-or-later
