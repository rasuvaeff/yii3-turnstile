<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;

#[CoversClass(TurnstileClient::class)]
final class TurnstileClientTest extends TestCase
{
    private TurnstileConfig $config;
    private ?RequestInterface $lastRequest = null;
    private TurnstileClient $client;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = new TurnstileConfig(
            siteKey: 'test-site-key',
            secret: 'test-secret',
            verifyUrl: 'https://verify.test/turnstile',
        );
        $this->lastRequest = null;
        $psr17 = new Psr17Factory();

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
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

    private Response $currentResponse;

    #[Test]
    public function verifySuccess(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true,"hostname":"example.com"}');

        $result = $this->client->verify(token: 'valid-token');

        $this->assertTrue($result->success);
        $this->assertSame('example.com', $result->hostname);
    }

    #[Test]
    public function verifyFailure(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->client->verify(token: 'bad-token');

        $this->assertFalse($result->success);
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    #[Test]
    public function verifySendsPostWithSecretAndToken(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'my-token');

        $this->assertNotNull($this->lastRequest);
        $this->assertSame('POST', $this->lastRequest->getMethod());
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=test-secret', $body);
        $this->assertStringContainsString('response=my-token', $body);
    }

    #[Test]
    public function verifyWithSecretUsesCustomSecret(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verifyWithSecret(token: 'token', secret: 'custom-secret');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=custom-secret', $body);
        $this->assertStringNotContainsString('secret=test-secret', $body);
    }

    #[Test]
    public function verifySendsIdempotencyKeyWhenProvided(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token', idempotencyKey: 'abc-123');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('idempotency_key=abc-123', $body);
    }

    #[Test]
    public function verifyOmitsIdempotencyKeyWhenNotProvided(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringNotContainsString('idempotency_key', $body);
    }

    #[Test]
    public function verifySendsRemoteIpWhenConfigured(): void
    {
        $config = new TurnstileConfig(siteKey: 'key', secret: 'secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return new Response(200, [], '{"success":true}');
            },
        );
        $client = new TurnstileClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $client->verify(token: 'token', clientIp: '1.2.3.4');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('remoteip=1.2.3.4', $body);
    }

    #[Test]
    public function verifyOmitsRemoteIpWhenNotConfigured(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token', clientIp: '1.2.3.4');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringNotContainsString('remoteip', $body);
    }

    #[Test]
    public function verifyParsesAllFields(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true,"hostname":"example.com","action":"login","challenge_ts":"2026-01-01T00:00:00Z"}');

        $result = $this->client->verify(token: 'token');

        $this->assertSame('example.com', $result->hostname);
        $this->assertSame('login', $result->action);
        $this->assertSame('2026-01-01T00:00:00Z', $result->challengeTs);
    }
}
