<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

/**
 * @api
 */
enum TurnstileSize: string
{
    case Normal = 'normal';
    case Compact = 'compact';
    case Flexible = 'flexible';
}
