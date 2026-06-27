<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Rasuvaeff\Yii3Turnstile\TurnstileSize;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(TurnstileSize::class)]
final class TurnstileSizeTest
{
    public function hasExpectedCases(): void
    {
        Assert::same(TurnstileSize::Normal->value, 'normal');
        Assert::same(TurnstileSize::Compact->value, 'compact');
        Assert::same(TurnstileSize::Flexible->value, 'flexible');
        Assert::same(TurnstileSize::Invisible->value, 'invisible');
    }
}
