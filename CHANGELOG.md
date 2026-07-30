# Changelog

## 1.0.1

- Security: reject `../`/empty/percent-encoded path segments in model IDs (`get_endpoint()`), preventing a caller-supplied ID from escaping the intended `:env/:brand/resource` path via relative-URI resolution.
- Security: `Config` now requires `https://` for `baseUri` unless `allowInsecureHttp: true` is explicitly passed, preventing an accidental cleartext downgrade of the bearer token/secrets.
- Security: sensitive-key redaction (`auth`, `secret`, `password`, `client_secret`) is now case-insensitive, recursive, and also applied to the captured error response body (previously only the outbound request payload was redacted).

## 1.0.0

- Initial release extracted from `wp-plugin-payments-shipping` Stripe Client.
- PHP 8.0+ Composer library (`newfold-labs/stripe-payments-api`, namespace `Bluehost\StripePaymentsAPI`).
- Ported models: Account, Token, AuthUrl, AuthToken, Customer, Intent, PaymentMethod, PublicKey, Transfer.
- Added Product, Price, and Payment Link management aligned with the YITH middleware and Stripe API shapes.
- Bug/security fixes: instance config, Guzzle transport, 2xx handling, validation deps, hash sanitization, signature store, secret redaction.
