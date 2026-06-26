<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests\Integration;

use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[CoversNothing]
final class TurnstileIntegrationTest
{
    private TurnstileClient $client;

    #[BeforeTest]
    public function setUp(): void
    {
        $secret = getenv('TURNSTILE_SECRET');
        if ($secret === false || $secret === '') {
            return;
        }

        $config = new TurnstileConfig(siteKey: '1x00000000000000000000AA', secret: $secret);
        $psr17 = new Psr17Factory();
        $httpClient = new Client();

        $this->client = new TurnstileClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    public function alwaysPassSecretReturnsSuccess(): void
    {
        if (!isset($this->client)) {
            return;
        }

        $result = $this->client->verify(token: 'dummy-token');

        Assert::true($result->success);
    }

    public function verifyReturnsHostname(): void
    {
        if (!isset($this->client)) {
            return;
        }

        $result = $this->client->verify(token: 'dummy-token');

        Assert::notNull($result->hostname);
    }
}
