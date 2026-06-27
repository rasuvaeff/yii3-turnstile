<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Rasuvaeff\Yii3Turnstile\VerificationResult;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(VerificationResult::class)]
final class VerificationResultTest
{
    public function successWithMinimalArgs(): void
    {
        $result = new VerificationResult(success: true);

        Assert::true($result->success);
        Assert::same($result->errorCodes, []);
        Assert::null($result->hostname);
        Assert::null($result->action);
        Assert::null($result->challengeTs);
    }

    public function failureWithAllFields(): void
    {
        $result = new VerificationResult(
            success: false,
            errorCodes: ['invalid-input-response'],
            hostname: 'example.com',
            action: 'login',
            challengeTs: '2026-01-01T00:00:00Z',
        );

        Assert::false($result->success);
        Assert::same($result->errorCodes, ['invalid-input-response']);
        Assert::same($result->hostname, 'example.com');
        Assert::same($result->action, 'login');
        Assert::same($result->challengeTs, '2026-01-01T00:00:00Z');
    }
}
