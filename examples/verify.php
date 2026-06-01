<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Rasuvaeff\Yii3Turnstile\TurnstileClient;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;

$config = new TurnstileConfig(
    siteKey: '1x00000000000000000000AA',
    secret: '1x0000000000000000000000000000000AA',
);

$psr17 = new Psr17Factory();
$httpClient = new GuzzleHttp\Client();

$client = new TurnstileClient(
    config: $config,
    httpClient: $httpClient,
    requestFactory: $psr17,
    streamFactory: $psr17,
);

$token = $argv[1] ?? 'test-token';
echo "Verifying token: {$token}\n";

$result = $client->verify(token: $token);

echo "Success: " . ($result->success ? 'yes' : 'no') . "\n";

if ($result->errorCodes !== []) {
    echo "Error codes: " . implode(', ', $result->errorCodes) . "\n";
}

if ($result->hostname !== null) {
    echo "Hostname: {$result->hostname}\n";
}
