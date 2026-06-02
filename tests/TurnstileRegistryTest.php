<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileRegistry;
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Rasuvaeff\Yii3Turnstile\TurnstileRuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\ValidationContext;

#[CoversClass(TurnstileRegistry::class)]
#[CoversClass(TurnstileRuleHandler::class)]
final class TurnstileRegistryTest extends TestCase
{
    private TurnstileClient $client;

    #[\Override]
    protected function setUp(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(new Response(200, [], '{"success":true}'));
        $this->client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }

    #[\Override]
    protected function tearDown(): void
    {
        TurnstileRegistry::configure(client: $this->client, requestProvider: null, translator: null);
    }

    #[Test]
    public function registryReturnsNullBeforeConfiguration(): void
    {
        $ref = new \ReflectionClass(TurnstileRegistry::class);
        $ref->getProperty('client')->setValue(null, null);
        $ref->getProperty('requestProvider')->setValue(null, null);
        $ref->getProperty('translator')->setValue(null, null);

        $this->assertNull(TurnstileRegistry::client());
        $this->assertNull(TurnstileRegistry::requestProvider());
        $this->assertNull(TurnstileRegistry::translator());
    }

    #[Test]
    public function registryStoresAndReturnsClient(): void
    {
        TurnstileRegistry::configure(client: $this->client);

        $this->assertSame($this->client, TurnstileRegistry::client());
    }

    #[Test]
    public function registryStoresOptionalDependencies(): void
    {
        $requestProvider = new RequestProvider();
        $translator = new Translator(locale: 'en');

        TurnstileRegistry::configure(
            client: $this->client,
            requestProvider: $requestProvider,
            translator: $translator,
        );

        $this->assertSame($requestProvider, TurnstileRegistry::requestProvider());
        $this->assertSame($translator, TurnstileRegistry::translator());
    }

    #[Test]
    public function handlerUsesRegistryClientWhenConstructedWithoutArgs(): void
    {
        TurnstileRegistry::configure(client: $this->client);

        $handler = new TurnstileRuleHandler();
        $result = $handler->validate('valid-token', new TurnstileRule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function handlerThrowsWhenClientNotAvailable(): void
    {
        $ref = new \ReflectionClass(TurnstileRegistry::class);
        $ref->getProperty('client')->setValue(null, null);

        $handler = new TurnstileRuleHandler();

        $this->expectException(\RuntimeException::class);
        $handler->validate('token', new TurnstileRule(), new ValidationContext());
    }

    #[Test]
    public function handlerUsesRegistryTranslatorFallback(): void
    {
        $translator = new Translator(locale: 'en');
        TurnstileRegistry::configure(client: $this->client, translator: $translator);

        $handler = new TurnstileRuleHandler();
        $result = $handler->validate('', new TurnstileRule(), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    #[Test]
    public function handlerUsesRegistryRequestProviderFallback(): void
    {
        $requestProvider = new RequestProvider();
        TurnstileRegistry::configure(client: $this->client, requestProvider: $requestProvider);

        $handler = new TurnstileRuleHandler();
        $result = $handler->validate('valid-token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function handlerPrefersInjectedClientOverRegistry(): void
    {
        $failingClient = $this->makeClient('{"success":false}');
        TurnstileRegistry::configure(client: $failingClient);

        $handler = new TurnstileRuleHandler(client: $this->client);
        $result = $handler->validate('token', new TurnstileRule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function handlerPrefersInjectedTranslatorOverRegistry(): void
    {
        $registryTranslator = $this->createMock(\Yiisoft\Translator\TranslatorInterface::class);
        $registryTranslator->expects($this->never())->method('translate');

        $injectedTranslator = $this->createMock(\Yiisoft\Translator\TranslatorInterface::class);
        $injectedTranslator->method('translate')->willReturn('error');

        TurnstileRegistry::configure(client: $this->client, translator: $registryTranslator);

        $handler = new TurnstileRuleHandler(client: $this->client, translator: $injectedTranslator);
        $handler->validate('', new TurnstileRule(), new ValidationContext());
    }

    #[Test]
    public function handlerPrefersInjectedRequestProviderOverRegistry(): void
    {
        $registryProvider = $this->createMock(RequestProviderInterface::class);
        $registryProvider->expects($this->never())->method('get');

        $injectedProvider = $this->createMock(RequestProviderInterface::class);
        $injectedProvider->method('get')->willThrowException(new \Yiisoft\RequestProvider\RequestNotSetException());

        TurnstileRegistry::configure(client: $this->client, requestProvider: $registryProvider);

        $handler = new TurnstileRuleHandler(client: $this->client, requestProvider: $injectedProvider);
        $handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());
    }

    private function makeClient(string $responseBody): TurnstileClient
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(new Response(200, [], $responseBody));
        return new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }
}
