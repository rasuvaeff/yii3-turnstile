# rasuvaeff/yii3-турникет
[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-turnstile?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-turnstile)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-turnstile/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-turnstile/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-turnstile/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-turnstile/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-turnstile/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-turnstile/php)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-turnstile)](LICENSE.md)
Виджет Cloudflare Turnstile CAPTCHA и серверный валидатор для Yii3.

 Предоставляет виджет Turnstile для отображения задачи в форме и пару
 TurnstileRule/TurnstileRuleHandler для проверки на стороне сервера через
 конвейер валидатора Yii. HTTP-вызовы проходят через любого клиента PSR-18.

 > **Используете помощника по кодированию с использованием искусственного интеллекта?** [llms.txt](llms.txt) содержит компактную ссылку
 > API, которой вы можете поделиться с моделью. Авторы: см. [AGENTS.md](AGENTS.md). @@ЛИНИЯ@@
## Требования
| Требование | Версия |
 |-------------|---------|
 | PHP | `^8.3` |
 | HTTP-клиент PSR-18 + фабрики PSR-17 | любая реализация |
 | `yiisoft/виджет` | `^2.2` |
 | `yiisoft/html` | `^3.13 || ^4.0` |
 | `yiisoft/валидатор` | `^2,5` |
 | `yiisoft/переводчик` | `^3.0` |
 | `yiisoft/поставщик запросов` | `^1.3` | @@ЛИНИЯ@@
## Установка
```bash
composer require rasuvaeff/yii3-turnstile
```
Вам также понадобится клиент PSR-18 и фабрики PSR-17, если
 еще не поставляет ваш проект:

```bash
composer require nyholm/psr7
# or: composer require guzzlehttp/guzzle
```
### Конфигурация цифрового входа
Начиная с версии 1.0.3 пакет поставляется `config/bootstrap.php` через `config-plugin`. При каждой загрузке приложения
 оно заполняет `TurnstileRegistry` зависимостями обработчика, поэтому
 `TurnstileRuleHandler` работает даже с `SimpleRuleHandlerContainer` по умолчанию` —
 **дополнительная настройка DI не требуется**.

 Если ваше приложение уже использует RuleHandlerContainer по другим причинам, сохраните его; этот пакет
 совместим с обоими преобразователями. @@ЛИНИЯ@@
## Использование
### 1. Отобразите виджет в форме.
```php
use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;

// siteKey comes from DI config (TurnstileConfig)
echo Turnstile::widget()
    ->withTheme(TurnstileTheme::Light)
    ->withSize(TurnstileSize::Normal)
    ->withResponseFieldName('turnstileResponse'); // match your FormModel property name
```
Вывод:

```html
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<div class="cf-turnstile" data-sitekey="your-site-key" data-response-field-name="cf-turnstile-response" data-theme="light" data-size="normal"></div>
```
### 2. Проверка на стороне сервера с помощью правила валидатора.
```php
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Yiisoft\Validator\Validator;

class LoginForm
{
    #[TurnstileRule]
    public string $turnstileResponse = '';
}
```
> **Сопоставление имен полей с помощью Yii3 FormModel**
 >
 > Виджет Cloudflare отправляет токен как `cf-turnstile-response` (с дефисами) по
 > по умолчанию. PHP **не** нормализует дефисы в ключах POST, поэтому задайте для
 > `withResponseFieldName()` PHP-совместимое имя, соответствующее свойству вашей модели:
 >
 > ```php
 > echo Turnstile::widget()->withResponseFieldName('turnstileResponse');
 > ```
 >
 > ```php
 > #[TurnstileRule]
 > public string $turnstileResponse = '';

 $result = (новый Валидатор())->validate($loginForm);
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
 'translation.category' => 'yii3-турникет',
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

decreate(strict_types=1);

 return [
 'Проверка CAPTCHA не удалась.' => 'Ваше переведенное сообщение.',
 ];
```

## Components

### `Turnstile` (widget)

Renders the Cloudflare Turnstile HTML + script tag. Extends `Yiisoft\Widget\Widget`.

| Method | Description |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Cloudflare site key (required). |
| `withTheme(TurnstileTheme $theme): self` | `Auto`, `Light`, or `Dark`. Default: `Auto`. |
| `withSize(TurnstileSize $size): self` | `Normal`, `Compact`, `Flexible`, or `Invisible`. Default: `Normal`. |
| `withResponseFieldName(string $name): self` | Name of the hidden input field. Default: `cf-turnstile-response`. |
| `withJsApiUrl(string $url): self` | Override the script URL. Default: Cloudflare CDN. |
| `render(): string` | Returns the HTML string. Throws if `siteKey` is not set. |

### `TurnstileConfig`

Immutable configuration DTO.

```php
окончательный класс только для чтения TurnstileConfig
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
окончательный класс только для чтения TurnstileClient
 {
 public function __construct(
 Private TurnstileConfig $config,
 Private ClientInterface $httpClient,
 Private RequestFactoryInterface $requestFactory,
 Private StreamFactoryInterface $streamFactory,
 ) {}

 public function verification(string $token, ?string $clientIp = null, ?string $idempotencyKey = null): VerificationResult;
 public functionverifyWithSecret(string $token, string $secret, ?string $clientIp = null, ?string $idempotencyKey = null): VerificationResult; @@ЛИНИЯ@@ }
```

`idempotencyKey` is an optional UUID that lets you safely re-verify the same
token (Cloudflare returns the original result instead of an error); it is only
sent when provided. `verifyWithSecret()` is used by the rule handler when a
per-rule `secret` override is set.

### `VerificationResult`

DTO returned by `TurnstileClient::verify()`.

```php
окончательный класс VerificationResult
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
#[TurnstileRule( сообщение
: 'Пользовательское сообщение об ошибке',
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
| `TurnstileTheme` | `At}o`, `Light`, `Dark` |
| `TurnstileSize` | `Normal`, `Compact`, `Flexible`, `Invisible` |

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
docker run --rm -v "$PWD":/app -w /app композитор:2 композитор install
 docker run --rm -v "$PWD":/app -w /app композитор:2 композитор сборка
 docker run --rm -v "$PWD":/app -w /app композитор:2 композитор cs:fix
 docker run --rm -v "$PWD":/app -w /app композитор:2 композитор тест
```

Or with Make:

```bash
make install
 make build
 make cs-fix
 make test
```

CI runs `composer build` on PHP 8.3, 8.4, and 8.5.

## License

[BSD-3-Clause](LICENSE.md)
