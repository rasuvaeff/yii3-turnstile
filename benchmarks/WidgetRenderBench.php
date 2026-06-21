<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Benchmarks;

use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Testo\Bench;

/**
 * Compares rendering Turnstile with minimal options vs all options set.
 */
final class WidgetRenderBench
{
    #[Bench(
        callables: [
            'full-options' => [self::class, 'renderWithAllOptions'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function renderMinimal(): string
    {
        return (new Turnstile())
            ->withSiteKey('1x00000000000000000000AA')
            ->render();
    }

    public static function renderWithAllOptions(): string
    {
        return (new Turnstile())
            ->withSiteKey('1x00000000000000000000AA')
            ->withTheme(TurnstileTheme::Dark)
            ->withSize(TurnstileSize::Compact)
            ->withResponseFieldName('cf-turnstile-response')
            ->render();
    }
}
