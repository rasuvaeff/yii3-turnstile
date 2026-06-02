<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileRegistry;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

return [
    static function (ContainerInterface $container): void {
        TurnstileRegistry::configure(
            client: $container->get(TurnstileClient::class),
            requestProvider: $container->has(RequestProviderInterface::class)
                ? $container->get(RequestProviderInterface::class)
                : null,
            translator: $container->has(TranslatorInterface::class)
                ? $container->get(TranslatorInterface::class)
                : null,
        );
    },
];
