<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileRuleHandler;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IdMessageReader;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;

/** @var array $params */

return [
    TurnstileConfig::class => [
        '__construct()' => [
            'siteKey' => $params['rasuvaeff/yii3-turnstile']['siteKey'],
            'secret' => $params['rasuvaeff/yii3-turnstile']['secret'],
            'verifyUrl' => $params['rasuvaeff/yii3-turnstile']['verifyUrl'],
            'sendRemoteIp' => $params['rasuvaeff/yii3-turnstile']['sendRemoteIp'],
        ],
    ],
    TurnstileRuleHandler::class => [
        '__construct()' => [
            'translationCategory' => $params['rasuvaeff/yii3-turnstile']['translation.category'],
        ],
    ],
    'yii3-turnstile.categorySource' => [
        'definition' => static function () use ($params): CategorySource {
            $reader = class_exists(MessageSource::class)
                ? new MessageSource(dirname(__DIR__) . '/messages')
                : new IdMessageReader();

            $formatter = extension_loaded('intl')
                ? new IntlMessageFormatter()
                : new SimpleMessageFormatter();

            return new CategorySource(
                $params['rasuvaeff/yii3-turnstile']['translation.category'],
                $reader,
                $formatter,
            );
        },
        'tags' => ['translation.categorySource'],
    ],
];
