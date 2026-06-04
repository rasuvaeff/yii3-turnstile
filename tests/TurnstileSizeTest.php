<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;

#[CoversClass(TurnstileSize::class)]
final class TurnstileSizeTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $this->assertSame('normal', TurnstileSize::Normal->value);
        $this->assertSame('compact', TurnstileSize::Compact->value);
        $this->assertSame('flexible', TurnstileSize::Flexible->value);
        $this->assertSame('invisible', TurnstileSize::Invisible->value);
    }
}
