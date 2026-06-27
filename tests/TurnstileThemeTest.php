<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(TurnstileTheme::class)]
final class TurnstileThemeTest
{
    public function hasExpectedCases(): void
    {
        Assert::same(TurnstileTheme::Auto->value, 'auto');
        Assert::same(TurnstileTheme::Light->value, 'light');
        Assert::same(TurnstileTheme::Dark->value, 'dark');
    }

    public static function allCasesProvider(): iterable
    {
        yield 'auto' => [TurnstileTheme::Auto];
        yield 'light' => [TurnstileTheme::Light];
        yield 'dark' => [TurnstileTheme::Dark];
    }

    #[DataProvider('allCasesProvider')]
    public function allCasesHaveNonEmptyValue(TurnstileTheme $theme): void
    {
        Assert::true($theme->value !== '' && $theme->value !== []);
    }
}
