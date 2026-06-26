<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileRegistry;
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Rasuvaeff\Yii3Turnstile\TurnstileRuleHandler;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\ValidationContext;

#[Test]
#[Covers(TurnstileRegistry::class)]
#[Covers(TurnstileRuleHandler::class)]
final class TurnstileRegistryTest
{
    private TurnstileClient $client;

    #[BeforeTest]
    public function setUp(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = (new FakeHttpClient())->withSendRequestCallback(
            fn(RequestInterface $request): Response => new Response(200, [], '{"success":true}'),
        );
        $this->client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }

    #[AfterTest]
    public function tearDown(): void
    {
        TurnstileRegistry::configure(client: $this->client, requestProvider: null, translator: null);
    }

    public function registryReturnsNullBeforeConfiguration(): void
    {
        $ref = new \ReflectionClass(TurnstileRegistry::class);
        $ref->getProperty('client')->setValue(null, null);
        $ref->getProperty('requestProvider')->setValue(null, null);
        $ref->getProperty('translator')->setValue(null, null);

        Assert::null(TurnstileRegistry::client());
        Assert::null(TurnstileRegistry::requestProvider());
        Assert::null(TurnstileRegistry::translator());
    }

    public function registryStoresAndReturnsClient(): void
    {
        TurnstileRegistry::configure(client: $this->client);

        Assert::same(TurnstileRegistry::client(), $this->client);
    }

    public function registryStoresOptionalDependencies(): void
    {
        $requestProvider = new RequestProvider();
        $translator = new Translator(locale: 'en');

        TurnstileRegistry::configure(
            client: $this->client,
            requestProvider: $requestProvider,
            translator: $translator,
        );

        Assert::same(TurnstileRegistry::requestProvider(), $requestProvider);
        Assert::same(TurnstileRegistry::translator(), $translator);
    }

    public function handlerUsesRegistryClientWhenConstructedWithoutArgs(): void
    {
        TurnstileRegistry::configure(client: $this->client);

        $handler = new TurnstileRuleHandler();
        $result = $handler->validate('valid-token', new TurnstileRule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function handlerThrowsWhenClientNotAvailable(): void
    {
        $ref = new \ReflectionClass(TurnstileRegistry::class);
        $ref->getProperty('client')->setValue(null, null);

        $handler = new TurnstileRuleHandler();

        Expect::exception(\RuntimeException::class);
        $handler->validate('token', new TurnstileRule(), new ValidationContext());
    }

    public function handlerUsesRegistryTranslatorFallback(): void
    {
        $translator = new Translator(locale: 'en');
        TurnstileRegistry::configure(client: $this->client, translator: $translator);

        $handler = new TurnstileRuleHandler();
        $result = $handler->validate('', new TurnstileRule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true($result->getErrors() !== []);
    }

    public function handlerUsesRegistryRequestProviderFallback(): void
    {
        $requestProvider = new RequestProvider();
        TurnstileRegistry::configure(client: $this->client, requestProvider: $requestProvider);

        $handler = new TurnstileRuleHandler();
        $result = $handler->validate('valid-token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function handlerPrefersInjectedClientOverRegistry(): void
    {
        $failingClient = $this->makeClient('{"success":false}');
        TurnstileRegistry::configure(client: $failingClient);

        $handler = new TurnstileRuleHandler(client: $this->client);
        $result = $handler->validate('token', new TurnstileRule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function handlerPrefersInjectedTranslatorOverRegistry(): void
    {
        $registryTranslator = new FakeTranslator(translation: 'registry-error');
        $injectedTranslator = new FakeTranslator(translation: 'error');

        TurnstileRegistry::configure(client: $this->client, translator: $registryTranslator);

        $handler = new TurnstileRuleHandler(client: $this->client, translator: $injectedTranslator);
        $handler->validate('', new TurnstileRule(), new ValidationContext());

        Assert::same($registryTranslator->callCount(), 0);
        Assert::same($injectedTranslator->callCount(), 1);
    }

    public function handlerPrefersInjectedRequestProviderOverRegistry(): void
    {
        $registryProvider = new FakeRequestProvider();
        $injectedProvider = new FakeRequestProvider();

        TurnstileRegistry::configure(client: $this->client, requestProvider: $registryProvider);

        $handler = new TurnstileRuleHandler(client: $this->client, requestProvider: $injectedProvider);
        $handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        Assert::same($registryProvider->callCount(), 0);
        Assert::same($injectedProvider->callCount(), 1);
    }

    private function makeClient(string $responseBody): TurnstileClient
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = (new FakeHttpClient())->withSendRequestCallback(
            fn(RequestInterface $request): Response => new Response(200, [], $responseBody),
        );

        return new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }
}
