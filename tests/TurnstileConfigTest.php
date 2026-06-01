<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;

#[CoversClass(TurnstileConfig::class)]
final class TurnstileConfigTest extends TestCase
{
    #[Test]
    public function storesAllValues(): void
    {
        $config = new TurnstileConfig(
            siteKey: 'test-key',
            secret: 'test-secret',
            verifyUrl: 'https://example.com/verify',
            sendRemoteIp: true,
        );

        $this->assertSame('test-key', $config->siteKey);
        $this->assertSame('test-secret', $config->secret);
        $this->assertSame('https://example.com/verify', $config->verifyUrl);
        $this->assertTrue($config->sendRemoteIp);
    }

    #[Test]
    public function usesDefaults(): void
    {
        $config = new TurnstileConfig(
            siteKey: 'key',
            secret: 'secret',
        );

        $this->assertSame('https://challenges.cloudflare.com/turnstile/v0/siteverify', $config->verifyUrl);
        $this->assertFalse($config->sendRemoteIp);
    }
}
