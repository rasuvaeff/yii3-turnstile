# Examples

Runnable, self-contained scripts demonstrating yii3-turnstile usage.

| Script | Shows | Needs server? |
|--------|-------|:-------------:|
| [`widget.php`](widget.php) | Rendering the Turnstile widget HTML | no |
| [`verify.php`](verify.php) | Server-side token verification via `TurnstileClient` | no |

## Running

```bash
docker run --rm -v "$PWD/..":/app -w /app composer:2 php examples/widget.php
docker run --rm -v "$PWD/..":/app -w /app composer:2 php examples/verify.php
```
