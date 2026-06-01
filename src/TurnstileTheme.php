<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

/**
 * @api
 */
enum TurnstileTheme: string
{
    case Auto = 'auto';
    case Light = 'light';
    case Dark = 'dark';
}
