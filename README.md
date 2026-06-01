# rasuvaeff/yii3-turnstile

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-turnstile?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-turnstile)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-turnstile/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-turnstile/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-turnstile/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-turnstile/actions)
[![Coverage](https://codecov.io/gh/rasuvaeff/yii3-turnstile/branch/master/graph/badge.svg)](https://codecov.io/gh/rasuvaeff/yii3-turnstile)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-turnstile/blob/master/psalm.xml)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-turnstile)](LICENSE.md)

Cloudflare Turnstile CAPTCHA widget and server-side validator for Yii3.

Provides a `Turnstile` widget for rendering the challenge in a form and a
`TurnstileRule` / `TurnstileRuleHandler` pair for server-side verification through
the Yii validator pipeline. HTTP calls go through any PSR-18 client.

> **Using an AI coding assistant?** [llms.txt](llms.txt) contains a compact
> API reference you can share with the model. Contributors: see [AGENTS.md](AGENTS.md).

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | `^8.3`  |
| A PSR-18 HTTP client + PSR-17 factories | any implementation |
| `yiisoft/widget` | `^2.2` |
| `yiisoft/html` | `^4.0` |
| `yiisoft/validator` | `^2.5` |
| `yiisoft/translator` | `^3.0` |
| `yiisoft/request-provider` | `^1.3` |

## Installation

```bash
composer require rasuvaeff/yii3-turnstile
```

You also need a PSR-18 client and PSR-17 factories if your project doesn't
already ship one:

```bash
composer require nyholm/psr7
# or: composer require guzzlehttp/guzzle
```

## Usage

### 1. Render the widget in a form

```php
use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;

// siteKey comes from DI config (TurnstileConfig)
echo Turnstile::widget()
    ->withTheme(TurnstileTheme::Light)
    ->withSize(TurnstileSize::Normal);
```

Output:

```html
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<div class="cf-turnstile" data-sitekey="your-site-key" data-response-field-name="cf-turnstile-response" data-theme="light" data-size="normal"></div>
```

### 2. Validate server-side with a validator rule

```php
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Yiisoft\Validator\Validator;

class LoginForm
{
    #[TurnstileRule(secret: 'your-secret')]
    public string $captcha = '';
}

$result = (new Validator())->validate($loginForm);
```

The rule sends the token to Cloudflare's `siteverify` endpoint and reports
success/failure through the standard Yii validator `Result`.

### 3. Dependency injection (Yii3)

The package ships `config/params.php` and `config/di.php` compatible with
`yiisoft/config`. Override params in your application config:

```php
// config/params.php
return [
    'rasuvaeff/yii3-turnstile' => [
        'siteKey' => $_ENV['TURNSTILE_SITE_KEY'],
        'secret' => $_ENV['TURNSTILE_SECRET'],
        'verifyUrl' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        'sendRemoteIp' => true,
        'translation.category' => 'yii3-turnstile',
    ],
];
```

The DI config registers a `CategorySource` tagged as `translation.categorySource`.
When `yiisoft/translator-message-php` is installed, it reads message files from
`messages/<locale>/yii3-turnstile.php`. Without it, message IDs are returned as-is.

### 4. Translations

The package includes Russian translations out of the box:

| Locale | File |
|--------|------|
| `ru` | `messages/ru/yii3-turnstile.php` |

To add more languages, create `messages/<locale>/yii3-turnstile.php`:

```php
<?php

declare(strict_types=1);

return [
    'The CAPTCHA verification failed.' => 'Your translated message.',
];
```

## Components

### `Turnstile` (widget)

Renders the Cloudflare Turnstile HTML + script tag. Extends `Yiisoft\Widget\Widget`.

| Method | Description |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Cloudflare site key (required). |
| `withTheme(TurnstileTheme $theme): self` | `Auto`, `Light`, or `Dark`. Default: `Auto`. |
| `withSize(TurnstileSize $size): self` | `Normal`, `Compact`, or `Flexible`. Default: `Normal`. |
| `withResponseFieldName(string $name): self` | Name of the hidden input field. Default: `cf-turnstile-response`. |
| `withJsApiUrl(string $url): self` | Override the script URL. Default: Cloudflare CDN. |
| `render(): string` | Returns the HTML string. Throws if `siteKey` is not set. |

### `TurnstileConfig`

Immutable configuration DTO.

```php
final readonly class TurnstileConfig
{
    public function __construct(
        public string $siteKey,
        public string $secret,
        public string $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        public bool $sendRemoteIp = false,
    ) {}
}
```

### `TurnstileClient`

Sends the token verification POST to Cloudflare. Requires PSR-18 + PSR-17.

```php
final readonly class TurnstileClient
{
    public function __construct(
        private TurnstileConfig $config,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function verify(string $token, ?string $clientIp = null, ?string $idempotencyKey = null): VerificationResult;
    public function verifyWithSecret(string $token, string $secret, ?string $clientIp = null, ?string $idempotencyKey = null): VerificationResult;
}
```

`idempotencyKey` is an optional UUID that lets you safely re-verify the same
token (Cloudflare returns the original result instead of an error); it is only
sent when provided. `verifyWithSecret()` is used by the rule handler when a
per-rule `secret` override is set.

### `VerificationResult`

DTO returned by `TurnstileClient::verify()`.

```php
final readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public array $errorCodes = [],
        public ?string $hostname = null,
        public ?string $action = null,
        public ?string $challengeTs = null,
    ) {}
}
```

### `TurnstileRule` / `TurnstileRuleHandler`

A `RuleInterface` for the Yii validator. The handler receives `TurnstileClient`
from DI and calls `verify()` with the token value. When `sendRemoteIp` is set,
the handler reads the client IP from the current request via
`yiisoft/request-provider` (`RequestProviderInterface::get()`, `REMOTE_ADDR`
server param); if no request is set the IP is simply omitted. Supports
`skipOnEmpty`, `skipOnError`, and `when` via standard validator traits.

```php
#[TurnstileRule(
    message: 'Custom error message',
    sendRemoteIp: true,
)]
public string $captcha = '';
```

| Method | Description |
|--------|-------------|
| `getHandler(): string` | Returns `TurnstileRuleHandler::class`. |
| `getMessage(): string` | Error message on failure. |
| `getSecret(): ?string` | Override secret (uses DI config if null). |
| `getSendRemoteIp(): bool` | Whether to forward client IP. |

### Enums

| Enum | Values |
|------|--------|
| `TurnstileTheme` | `Auto`, `Light`, `Dark` |
| `TurnstileSize` | `Normal`, `Compact`, `Flexible` |

## Security

- The widget renders a **public** site key in HTML — this is intentional and safe.
- The **secret** is only used server-side in `TurnstileClient` and never reaches the browser.
- Token verification goes over HTTPS to Cloudflare's `siteverify` endpoint.
- `sendRemoteIp` is opt-in (disabled by default); the client IP is taken from the
  current request via `RequestProviderInterface` (`REMOTE_ADDR`), not from user input.

## Examples

See [examples/](examples/) for runnable scripts.

| Script | Shows | Needs server? |
|--------|-------|:-------------:|
| [`widget.php`](examples/widget.php) | Rendering the Turnstile widget | no |
| [`verify.php`](examples/verify.php) | Server-side token verification | no |

## Development

No PHP/Composer on the host — run in Docker via the `composer:2` image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

Or with Make:

```bash
make install
make build
make cs:fix
make test
```

CI runs `composer build` on PHP 8.3, 8.4, and 8.5.

## License

[BSD-3-Clause](LICENSE.md)
