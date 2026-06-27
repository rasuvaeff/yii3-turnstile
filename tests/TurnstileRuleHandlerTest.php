<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileRule;
use Rasuvaeff\Yii3Turnstile\TurnstileRuleHandler;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

#[Test]
#[Covers(TurnstileRule::class)]
#[Covers(TurnstileRuleHandler::class)]
final class TurnstileRuleHandlerTest
{
    private TurnstileRuleHandler $handler;

    private TurnstileClient $client;

    private ?RequestInterface $lastRequest = null;

    private Response $mockResponse;

    #[BeforeTest]
    public function setUp(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'test-secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = (new FakeHttpClient())->withSendRequestCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->mockResponse;
            },
        );
        $this->client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $this->handler = new TurnstileRuleHandler(client: $this->client);
    }

    public function validTokenPasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('valid-token', new TurnstileRule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function invalidTokenFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('bad-token', new TurnstileRule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function emptyValueFails(): void
    {
        $result = $this->handler->validate('', new TurnstileRule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function nonStringValueFails(): void
    {
        $result = $this->handler->validate(123, new TurnstileRule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function customMessageIsUsed(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false}');

        $result = $this->handler->validate('token', new TurnstileRule(message: 'Custom error'), new ValidationContext());

        Assert::false($result->isValid());
        Assert::contains($result->getErrorMessages(), 'Custom error');
    }

    public function sendRemoteIpPassesClientIpFromRequest(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => '1.2.3.4']),
        );
        $handler = new TurnstileRuleHandler(client: $this->client, requestProvider: $requestProvider);

        $result = $handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('remoteip=1.2.3.4');
    }

    public function sendRemoteIpOmitsIpWhenNoRequestAvailable(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->notContains('remoteip');
    }

    public function ruleSecretOverridesConfigSecret(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('token', new TurnstileRule(secret: 'rule-secret'), new ValidationContext());

        Assert::true($result->isValid());
        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('secret=rule-secret');
        Assert::string($body)->notContains('secret=test-secret');
    }

    public function ruleReturnsHandlerClass(): void
    {
        Assert::same((new TurnstileRule())->getHandler(), TurnstileRuleHandler::class);
    }

    public function throwsOnUnexpectedRule(): void
    {
        Expect::exception(UnexpectedRuleException::class);

        $this->handler->validate('token', new class implements RuleInterface {
            #[\Override]
            public function getHandler(): string|RuleHandlerInterface
            {
                return 'not-turnstile';
            }
        }, new ValidationContext());
    }

    public function emptyValueErrorIncludesPropertyParameter(): void
    {
        $context = (new ValidationContext())->setPropertyLabel('captcha');

        $result = $this->handler->validate('', new TurnstileRule(), $context);

        Assert::false($result->isValid());
        Assert::same($result->getErrors()[0]->getParameters(), ['property' => 'captcha']);
    }

    public function apiFailureErrorIncludesParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');
        $context = (new ValidationContext())->setPropertyLabel('captcha');

        $result = $this->handler->validate('token', new TurnstileRule(), $context);

        Assert::false($result->isValid());
        Assert::same(
            $result->getErrors()[0]->getParameters(),
            ['property' => 'captcha', 'errorCodes' => 'invalid-input-response'],
        );
    }

    public function omitsRemoteIpWhenRemoteAddrIsNotString(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => 12345]),
        );
        $handler = new TurnstileRuleHandler(client: $this->client, requestProvider: $requestProvider);

        $result = $handler->validate('token', new TurnstileRule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::notNull($this->lastRequest);
        Assert::string($this->lastRequest->getBody()->__toString())->notContains('remoteip=');
    }

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
        $httpClient = (new FakeHttpClient())->withSendRequestCallback(fn(): Response => $this->mockResponse);
        $client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $handler = new TurnstileRuleHandler(client: $client, translator: $translator, translationCategory: 'yii3-turnstile');

        $result = $handler->validate('token', new TurnstileRule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::contains($result->getErrorMessages(), 'Проверка CAPTCHA не удалась.');
    }
}
