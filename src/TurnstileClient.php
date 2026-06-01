<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @api
 */
final readonly class TurnstileClient
{
    public function __construct(
        private TurnstileConfig $config,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function verify(string $token, ?string $clientIp = null, ?string $idempotencyKey = null): VerificationResult
    {
        return $this->doVerify(
            secret: $this->config->secret,
            token: $token,
            clientIp: $clientIp,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function verifyWithSecret(string $token, string $secret, ?string $clientIp = null, ?string $idempotencyKey = null): VerificationResult
    {
        return $this->doVerify(
            secret: $secret,
            token: $token,
            clientIp: $clientIp,
            idempotencyKey: $idempotencyKey,
        );
    }

    private function doVerify(string $secret, string $token, ?string $clientIp, ?string $idempotencyKey): VerificationResult
    {
        $body = http_build_query(
            data: array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $this->config->sendRemoteIp ? $clientIp : null,
                'idempotency_key' => $idempotencyKey,
            ]),
        );

        $request = $this->requestFactory
            ->createRequest('POST', $this->config->verifyUrl)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->httpClient->sendRequest($request);

        /** @var array{success: bool, error-codes?: string[], hostname?: string, action?: string, challenge_ts?: string} $data */
        $data = json_decode(
            json: $response->getBody()->__toString(),
            associative: true,
            depth: 512,
            flags: JSON_THROW_ON_ERROR,
        );

        return new VerificationResult(
            success: $data['success'],
            errorCodes: $data['error-codes'] ?? [],
            hostname: $data['hostname'] ?? null,
            action: $data['action'] ?? null,
            challengeTs: $data['challenge_ts'] ?? null,
        );
    }
}
