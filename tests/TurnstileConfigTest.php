<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(TurnstileConfig::class)]
final class TurnstileConfigTest
{
    public function storesAllValues(): void
    {
        $config = new TurnstileConfig(
            siteKey: 'test-key',
            secret: 'test-secret',
            verifyUrl: 'https://example.com/verify',
            sendRemoteIp: true,
        );

        Assert::same($config->siteKey, 'test-key');
        Assert::same($config->secret, 'test-secret');
        Assert::same($config->verifyUrl, 'https://example.com/verify');
        Assert::true($config->sendRemoteIp);
    }

    public function usesDefaults(): void
    {
        $config = new TurnstileConfig(
            siteKey: 'key',
            secret: 'secret',
        );

        Assert::same($config->verifyUrl, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
        Assert::false($config->sendRemoteIp);
    }
}
