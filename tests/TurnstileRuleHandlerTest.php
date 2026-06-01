<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Rasuvaeff\Yii3Turnstile\TurnstileRuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

#[CoversClass(TurnstileRule::class)]
#[CoversClass(TurnstileRuleHandler::class)]
final class TurnstileRuleHandlerTest extends TestCase
{
    private TurnstileRuleHandler $handler;
    private TurnstileClient $client;
    private ?RequestInterface $lastRequest = null;
    private Response $mockResponse;

    #[\Override]
    protected function setUp(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->mockResponse;
            },
        );
        $this->client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $this->handler = new TurnstileRuleHandler(client: $this->client);
    }

    #[Test]
    public function validTokenPasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('valid-token', new TurnstileRule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function invalidTokenFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('bad-token', new TurnstileRule(), new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function emptyValueFails(): void
    {
        $result = $this->handler->validate('', new TurnstileRule(), new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function nonStringValueFails(): void
    {
        $result = $this->handler->validate(123, new TurnstileRule(), new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function customMessageIsUsed(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false}');

        $result = $this->handler->validate('token', new TurnstileRule(message: 'Custom error'), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertContains('Custom error', $result->getErrorMessages());
    }

    #[Test]
    public function sendRemoteIpPassesClientIpFromRequest(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => '1.2.3.4']),
        );
        $handler = new TurnstileRuleHandler(client: $this->client, requestProvider: $requestProvider);

        $result = $handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('remoteip=1.2.3.4', $body);
    }

    #[Test]
    public function sendRemoteIpOmitsIpWhenNoRequestAvailable(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringNotContainsString('remoteip', $body);
    }

    #[Test]
    public function ruleSecretOverridesConfigSecret(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('token', new TurnstileRule(secret: 'rule-secret'), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=rule-secret', $body);
        $this->assertStringNotContainsString('secret=test-secret', $body);
    }

    #[Test]
    public function ruleReturnsHandlerClass(): void
    {
        $this->assertSame(TurnstileRuleHandler::class, (new TurnstileRule())->getHandler());
    }

    #[Test]
    public function throwsOnUnexpectedRule(): void
    {
        $this->expectException(UnexpectedRuleException::class);

        $this->handler->validate('token', $this->createMock(RuleInterface::class), new ValidationContext());
    }

    #[Test]
    public function emptyValueErrorIncludesPropertyParameter(): void
    {
        $context = (new ValidationContext())->setPropertyLabel('captcha');

        $result = $this->handler->validate('', new TurnstileRule(), $context);

        $this->assertFalse($result->isValid());
        $this->assertSame(['property' => 'captcha'], $result->getErrors()[0]->getParameters());
    }

    #[Test]
    public function apiFailureErrorIncludesParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');
        $context = (new ValidationContext())->setPropertyLabel('captcha');

        $result = $this->handler->validate('token', new TurnstileRule(), $context);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            ['property' => 'captcha', 'errorCodes' => 'invalid-input-response'],
            $result->getErrors()[0]->getParameters(),
        );
    }

    #[Test]
    public function omitsRemoteIpWhenRemoteAddrIsNotString(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => 12345]),
        );
        $handler = new TurnstileRuleHandler(client: $this->client, requestProvider: $requestProvider);

        $result = $handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $this->assertStringNotContainsString('remoteip=', $this->lastRequest->getBody()->__toString());
    }

    #[Test]
    public function translatorTranslatesErrorMessage(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false}');

        $translator = new Translator(locale: 'ru');
        $categorySource = new CategorySource(
            'yii3-turnstile',
            new MessageSource(dirname(__DIR__) . '/messages'),
            extension_loaded('intl')
                ? new IntlMessageFormatter()
                : new SimpleMessageFormatter(),
        );
        $translator->addCategorySources($categorySource);

        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($this->mockResponse);
        $client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $handler = new TurnstileRuleHandler(client: $client, translator: $translator, translationCategory: 'yii3-turnstile');

        $result = $handler->validate('token', new TurnstileRule(), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertContains('Проверка CAPTCHA не удалась.', $result->getErrorMessages());
    }
}
