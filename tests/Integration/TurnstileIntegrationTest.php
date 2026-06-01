<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;

#[CoversNothing]
final class TurnstileIntegrationTest extends TestCase
{
    private TurnstileClient $client;

    #[\Override]
    protected function setUp(): void
    {
        $secret = $_ENV['TURNSTILE_SECRET'] ?? null;

        if ($secret === null) {
            $this->markTestSkipped('TURNSTILE_SECRET env variable is not set');
        }

        $config = new TurnstileConfig(siteKey: '1x00000000000000000000AA', secret: $secret);
        $psr17 = new Psr17Factory();

        $httpClient = new \GuzzleHttp\Client();

        $this->client = new TurnstileClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    #[Test]
    public function alwaysPassSecretReturnsSuccess(): void
    {
        $result = $this->client->verify(token: 'dummy-token');

        $this->assertTrue($result->success);
    }

    #[Test]
    public function verifyReturnsHostname(): void
    {
        $result = $this->client->verify(token: 'dummy-token');

        $this->assertNotNull($result->hostname);
    }
}
