<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

/**
 * @api
 */
final readonly class TurnstileConfig
{
    public function __construct(
        public string $siteKey,
        public string $secret,
        public string $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        public bool $sendRemoteIp = false,
    ) {}
}
