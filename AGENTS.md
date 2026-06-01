# AGENTS.md — yii3-turnstile

Guidance for AI agents working on this package. Read before changing code.

## What this is

A Cloudflare Turnstile CAPTCHA integration for Yii3 (PHP 8.3+). Provides a
`Turnstile` widget for rendering the challenge in forms and a
`TurnstileRule` / `TurnstileRuleHandler` pair for server-side verification via
the Yii validator pipeline. HTTP calls go through any PSR-18 client.

Public API (namespace `Rasuvaeff\Yii3Turnstile\`):

- `Turnstile` — widget (`Yiisoft\Widget\Widget` subclass)
- `TurnstileConfig` — immutable configuration DTO
- `TurnstileClient` — PSR-18 siteverify client
- `VerificationResult` — verification response DTO
- `TurnstileRule` / `TurnstileRuleHandler` — Yii validator rule + handler
- `TurnstileTheme`, `TurnstileSize` — backed string enums

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Secret must never leak client-side.** The widget uses `siteKey` (public);
   `secret` is server-side only.
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
```

`composer.lock` is gitignored (library).

## Invariants & gotchas

- `Turnstile` widget is `final class` (not readonly) — it uses `clone` in `with*`
  methods per yiisoft/widget convention.
- `TurnstileRule` is `final class` (not readonly) — traits need mutable `$skipOnEmpty`.
- `TurnstileClient`, `TurnstileConfig`, `VerificationResult`, `TurnstileRuleHandler`
  are `final readonly class`.
- `TurnstileRuleHandler::validate()` guards against wrong rule type with
  `UnexpectedRuleException` from `Yiisoft\Validator\Exception`.
- Token verification POSTs to Cloudflare via PSR-18; body is
  `application/x-www-form-urlencoded`.
- `TurnstileClient::verify()` uses the config secret; `verifyWithSecret()` accepts
  a per-rule secret override. Both accept an optional `idempotencyKey`.
- When `sendRemoteIp` is set, `TurnstileRuleHandler` reads the client IP from the
  current request via `RequestProviderInterface` (`REMOTE_ADDR`), not from the
  validation context. Omitted when no request is set (`RequestNotSetException`).
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build` and paste the output.
