<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(TurnstileClient::class)]
final class TurnstileClientTest
{
    private TurnstileConfig $config;

    private ?RequestInterface $lastRequest = null;

    private TurnstileClient $client;

    private Response $currentResponse;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->config = new TurnstileConfig(
            siteKey: 'test-site-key',
            secret: 'test-secret',
            verifyUrl: 'https://verify.test/turnstile',
        );
        $this->lastRequest = null;
        $psr17 = new Psr17Factory();

        $httpClient = (new FakeHttpClient())->withSendRequestCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->currentResponse;
            },
        );

        $this->client = new TurnstileClient(
            config: $this->config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    public function verifySuccess(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true,"hostname":"example.com"}');

        $result = $this->client->verify(token: 'valid-token');

        Assert::true($result->success);
        Assert::same($result->hostname, 'example.com');
    }

    public function verifyFailure(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->client->verify(token: 'bad-token');

        Assert::false($result->success);
        Assert::same($result->errorCodes, ['invalid-input-response']);
    }

    public function verifySendsPostWithSecretAndToken(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'my-token');

        Assert::notNull($this->lastRequest);
        Assert::same($this->lastRequest->getMethod(), 'POST');
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('secret=test-secret');
        Assert::string($body)->contains('response=my-token');
    }

    public function verifyWithSecretUsesCustomSecret(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verifyWithSecret(token: 'token', secret: 'custom-secret');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('secret=custom-secret');
        Assert::string($body)->notContains('secret=test-secret');
    }

    public function verifySendsIdempotencyKeyWhenProvided(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token', idempotencyKey: 'abc-123');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('idempotency_key=abc-123');
    }

    public function verifyOmitsIdempotencyKeyWhenNotProvided(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->notContains('idempotency_key');
    }

    public function verifySendsRemoteIpWhenConfigured(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = (new FakeHttpClient())->withSendRequestCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return new Response(200, [], '{"success":true}');
            },
        );
        $client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $client->verify(token: 'token', clientIp: '1.2.3.4');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('remoteip=1.2.3.4');
    }

    public function verifyOmitsRemoteIpWhenNotConfigured(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token', clientIp: '1.2.3.4');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->notContains('remoteip');
    }

    public function verifyParsesAllFields(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true,"hostname":"example.com","action":"login","challenge_ts":"2026-01-01T00:00:00Z"}');

        $result = $this->client->verify(token: 'token');

        Assert::same($result->hostname, 'example.com');
        Assert::same($result->action, 'login');
        Assert::same($result->challengeTs, '2026-01-01T00:00:00Z');
    }

    public function verifySendRemoteIpWithEmptyClientIpOmitsRemoteIp(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = (new FakeHttpClient())->withSendRequestCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return new Response(200, [], '{"success":true}');
            },
        );
        $client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $client->verify(token: 'token', clientIp: '');

        Assert::notNull($this->lastRequest);
        Assert::string($this->lastRequest->getBody()->__toString())->notContains('remoteip');
    }

    public function verifyParsesJsonAtMaxAllowedDepth(): void
    {
        $inner = 'true';
        for ($i = 0; $i < 510; $i++) {
            $inner = '[' . $inner . ']';
        }
        $json = '{"success":true,"_":' . $inner . '}';

        $this->currentResponse = new Response(200, [], $json);

        $result = $this->client->verify(token: 'token');

        Assert::true($result->success);
    }

    public function verifyThrowsForJsonExceedingDepth512(): void
    {
        $inner = 'true';
        for ($i = 0; $i < 511; $i++) {
            $inner = '[' . $inner . ']';
        }
        $json = '{"success":true,"_":' . $inner . '}';

        $this->currentResponse = new Response(200, [], $json);

        Expect::exception(\JsonException::class);
        $this->client->verify(token: 'token');
    }
}
