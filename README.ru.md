# rasuvaeff/yii3-turnstile

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-turnstile?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-turnstile)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-turnstile/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-turnstile/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-turnstile/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-turnstile/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-turnstile/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-turnstile/php)](https://packagist.org/packages/rasuvaeff/yii3-turnstile)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-turnstile)](LICENSE.md)
[English version](README.md)

Виджет Cloudflare Turnstile CAPTCHA и серверный валидатор для Yii3.

Предоставляет виджет `Turnstile` для отрисовки челленджа в форме и пару
`TurnstileRule` / `TurnstileRuleHandler` для серверной проверки через конвейер
валидатора Yii. HTTP-вызовы идут через любой PSR-18-клиент.

> **Используете AI-ассистента?** В [llms.txt](llms.txt) — компактный API-справочник,
> которым можно поделиться с моделью. Для контрибьюторов: см. [AGENTS.md](AGENTS.md).

## Требования

| Требование | Версия |
|-------------|---------|
| PHP         | `^8.3`  |
| PSR-18 HTTP-клиент + PSR-17-фабрики | любая реализация |
| `yiisoft/widget` | `^2.2` |
| `yiisoft/html` | `^3.13 || ^4.0` |
| `yiisoft/validator` | `^2.5` |
| `yiisoft/translator` | `^3.0` |
| `yiisoft/request-provider` | `^1.3` |

## Установка

```bash
composer require rasuvaeff/yii3-turnstile
```

Также потребуется PSR-18-клиент и PSR-17-фабрики, если их ещё нет в проекте:

```bash
composer require nyholm/psr7
# или: composer require guzzlehttp/guzzle
```

### Конфигурация DI

Начиная с v1.0.3 пакет поставляет `config/bootstrap.php` через `config-plugin`. При
каждой загрузке приложения он наполняет `TurnstileRegistry` зависимостями обработчика,
поэтому `TurnstileRuleHandler` работает даже со стандартным
`SimpleRuleHandlerContainer` — **дополнительная DI-конфигурация не требуется**.

Если приложение уже использует `RuleHandlerContainer` для других целей — оставьте его;
пакет совместим с обоими resolver-ами.

## Использование

### 1. Отрисовка виджета в форме

```php
use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;

// siteKey берётся из DI-конфигурации (TurnstileConfig)
echo Turnstile::widget()
    ->withTheme(TurnstileTheme::Light)
    ->withSize(TurnstileSize::Normal)
    ->withResponseFieldName('turnstileResponse'); // должно совпадать с именем свойства в FormModel
```

Вывод:

```html
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<div class="cf-turnstile" data-sitekey="your-site-key" data-response-field-name="cf-turnstile-response" data-theme="light" data-size="normal"></div>
```

### 2. Серверная валидация правилом

```php
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Yiisoft\Validator\Validator;

class LoginForm
{
    #[TurnstileRule]
    public string $turnstileResponse = '';
}
```

> **Сопоставление имени поля с Yii3 FormModel**
>
> Виджет Cloudflare по умолчанию отправляет токен в поле `cf-turnstile-response`
> (с дефисами). PHP **не** нормализует дефисы в POST-ключах, поэтому задайте
> в `withResponseFieldName()` PHP-совместимое имя, совпадающее со свойством модели:
>
> ```php
> echo Turnstile::widget()->withResponseFieldName('turnstileResponse');
> ```
>
> ```php
> #[TurnstileRule]
> public string $turnstileResponse = '';
> ```

```php
$result = (new Validator())->validate($loginForm);
```

Правило отправляет токен на эндпоинт `siteverify` сервиса Cloudflare и сообщает
об успехе/неудаче через стандартный `Result` валидатора Yii.

### 3. Внедрение зависимостей (Yii3)

Пакет поставляет `config/params.php` и `config/di.php`, совместимые с
`yiisoft/config`. Переопределите параметры в конфигурации приложения:

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

DI-конфигурация регистрирует `CategorySource` с тегом `translation.categorySource`.
Если установлен `yiisoft/translator-message-php`, файлы сообщений читаются из
`messages/<locale>/yii3-turnstile.php`. Без него ID сообщений возвращаются как есть.

### 4. Переводы

В комплекте идут русские переводы:

| Локаль | Файл |
|--------|------|
| `ru` | `messages/ru/yii3-turnstile.php` |

Чтобы добавить другие языки, создайте `messages/<locale>/yii3-turnstile.php`:

```php
<?php

declare(strict_types=1);

return [
    'The CAPTCHA verification failed.' => 'Ваш переведённое сообщение.',
];
```

## Компоненты

### `Turnstile` (виджет)

Отрисовывает HTML виджета Cloudflare Turnstile + тег `<script>$. Наследуется от `Yiisoft\Widget\Widget`.

| Метод | Описание |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Cloudflare site key (обязательный). |
| `withTheme(TurnstileTheme $theme): self` | `Auto`, `Light` или `Dark`. По умолчанию: `Auto`. |
| `withSize(TurnstileSize $size): self` | `Normal`, `Compact`, `Flexible` или `Invisible`. По умолчанию: `Normal`. |
| `withResponseFieldName(string $name): self` | Имя скрытого поля ввода. По умолчанию: `cf-turnstile-response`. |
| `withJsApiUrl(string $url): self` | Переопределяет URL скрипта. По умолчанию: Cloudflare CDN. |
| `render(): string` | Возвращает HTML-строку. Бросает исключение, если `siteKey` не задан. |

### `TurnstileConfig`

Иммутабельный конфигурационный DTO.

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

Отправляет POST-запрос верификации токена в Cloudflare. Требует PSR-18 + PSR-17.

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

`idempotencyKey` — опциональный UUID, позволяющий безопасно повторно проверить
тот же токен (Cloudflare возвращает исходный результат вместо ошибки); отправляется
только если передан. `verifyWithSecret()` используется обработчиком правила, когда
задан переопределённый `secret` на уровне правила.

### `VerificationResult`

DTO, возвращаемый `TurnstileClient::verify()`.

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

`RuleInterface` для валидатора Yii. Обработчик получает `TurnstileClient` из DI и
вызывает `verify()` со значением токена. Если установлен `sendRemoteIp`, обработчик
читает IP клиента из текущего запроса через `yiisoft/request-provider`
(`RequestProviderInterface::get()`, серверный параметр `REMOTE_ADDR`); если запрос
не задан — IP просто опускается. Поддерживает `skipOnEmpty`, `skipOnError` и `when`
через стандартные trait-ы валидатора.

```php
#[TurnstileRule(
    message: 'Кастомное сообщение об ошибке',
    sendRemoteIp: true,
)]
public string $captcha = '';
```

| Метод | Описание |
|--------|-------------|
| `getHandler(): string` | Возвращает `TurnstileRuleHandler::class`. |
| `getMessage(): string` | Сообщение об ошибке при провале. |
| `getSecret(): ?string` | Переопределение secret (если `null` — из DI-конфига). |
| `getSendRemoteIp(): bool` | Передавать ли IP клиента. |

### Enum-ы

| Enum | Значения |
|------|--------|
| `TurnstileTheme` | `Auto`, `Light`, `Dark` |
| `TurnstileSize` | `Normal`, `Compact`, `Flexible`, `Invisible` |

## Безопасность

- Виджет отрисовывает в HTML **публичный** site key — это сделано намеренно и безопасно.
- **Secret** используется только на сервере в `TurnstileClient` и никогда не попадает в браузер.
- Верификация токена идёт по HTTPS на эндпоинт `siteverify` сервиса Cloudflare.
- `sendRemoteIp` opt-in (по умолчанию выключен); IP клиента берётся из текущего
  запроса через `RequestProviderInterface` (`REMOTE_ADDR`), а не из пользовательского ввода.

## Примеры

См. [examples/](examples/) — запускаемые скрипты.

| Скрипт | Показывает | Нужен сервер? |
|--------|-------|:-------------:|
| [`widget.php`](examples/widget.php) | Отрисовка виджета Turnstile | нет |
| [`verify.php`](examples/verify.php) | Серверная верификация токена | нет |

## Разработка

На хосте нет PHP/Composer — запускайте через Docker-образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

Или через Make:

```bash
make install
make build
make cs-fix
make test
```

CI прогоняет `composer build` на PHP 8.3, 8.4 и 8.5.

## Лицензия

[BSD-3-Clause](LICENSE.md)
