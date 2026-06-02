<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Static registry populated during application bootstrap.
 * Allows TurnstileRuleHandler to work with SimpleRuleHandlerContainer (no-arg construction).
 */
final class TurnstileRegistry
{
    private static ?TurnstileClient $client = null;
    private static ?RequestProviderInterface $requestProvider = null;
    private static ?TranslatorInterface $translator = null;

    /** @api */
    public static function configure(
        TurnstileClient $client,
        ?RequestProviderInterface $requestProvider = null,
        ?TranslatorInterface $translator = null,
    ): void {
        self::$client = $client;
        self::$requestProvider = $requestProvider;
        self::$translator = $translator;
    }

    public static function client(): ?TurnstileClient
    {
        return self::$client;
    }

    public static function requestProvider(): ?RequestProviderInterface
    {
        return self::$requestProvider;
    }

    public static function translator(): ?TranslatorInterface
    {
        return self::$translator;
    }
}
