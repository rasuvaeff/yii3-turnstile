<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;

#[CoversClass(TurnstileTheme::class)]
final class TurnstileThemeTest extends TestCase
{
    #[Test]
    public function hasExpectedCases(): void
    {
        $this->assertSame('auto', TurnstileTheme::Auto->value);
        $this->assertSame('light', TurnstileTheme::Light->value);
        $this->assertSame('dark', TurnstileTheme::Dark->value);
    }

    /**
     * @return iterable<string, array{TurnstileTheme}>
     */
    public static function allCasesProvider(): iterable
    {
        yield 'auto' => [TurnstileTheme::Auto];
        yield 'light' => [TurnstileTheme::Light];
        yield 'dark' => [TurnstileTheme::Dark];
    }

    #[DataProvider('allCasesProvider')]
    #[Test]
    public function allCasesHaveNonEmptyValue(TurnstileTheme $theme): void
    {
        $this->assertNotEmpty($theme->value);
    }
}
