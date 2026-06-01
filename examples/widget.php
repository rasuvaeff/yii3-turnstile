<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;

$config = new TurnstileConfig(siteKey: '1x00000000000000000000AA', secret: 'test');

echo "=== Default widget ===\n";
echo (new Turnstile(config: $config))->render();
echo "\n\n";

echo "=== Light theme, compact ===\n";
echo (new Turnstile(config: $config))
    ->withTheme(TurnstileTheme::Light)
    ->withSize(TurnstileSize::Compact)
    ->render();
echo "\n\n";

echo "=== Dark theme, flexible, custom response field ===\n";
echo (new Turnstile(config: $config))
    ->withTheme(TurnstileTheme::Dark)
    ->withSize(TurnstileSize::Flexible)
    ->withResponseFieldName('captcha_response')
    ->render();
echo "\n";
