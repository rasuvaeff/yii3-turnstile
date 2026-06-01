<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Rasuvaeff\Yii3Turnstile\TurnstileRuleHandler;

#[CoversClass(TurnstileRule::class)]
final class TurnstileRuleTest extends TestCase
{
    #[Test]
    public function usesDefaults(): void
    {
        $rule = new TurnstileRule();

        $this->assertSame('The CAPTCHA verification failed.', $rule->getMessage());
        $this->assertNull($rule->getSecret());
        $this->assertFalse($rule->getSendRemoteIp());
        $this->assertNull($rule->getSkipOnEmpty());
        $this->assertFalse($rule->shouldSkipOnError());
        $this->assertNull($rule->getWhen());
    }

    #[Test]
    public function storesAllValues(): void
    {
        $when = static fn(): bool => true;

        $rule = new TurnstileRule(
            message: 'Prove you are human',
            secret: 'override-secret',
            sendRemoteIp: true,
            skipOnEmpty: true,
            skipOnError: true,
            when: $when,
        );

        $this->assertSame('Prove you are human', $rule->getMessage());
        $this->assertSame('override-secret', $rule->getSecret());
        $this->assertTrue($rule->getSendRemoteIp());
        $this->assertTrue($rule->getSkipOnEmpty());
        $this->assertTrue($rule->shouldSkipOnError());
        $this->assertSame($when, $rule->getWhen());
    }

    #[Test]
    public function pointsToItsHandler(): void
    {
        $rule = new TurnstileRule();

        $this->assertSame(TurnstileRuleHandler::class, $rule->getHandler());
    }
}
