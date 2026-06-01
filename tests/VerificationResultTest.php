<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\VerificationResult;

#[CoversClass(VerificationResult::class)]
final class VerificationResultTest extends TestCase
{
    #[Test]
    public function successWithMinimalArgs(): void
    {
        $result = new VerificationResult(success: true);

        $this->assertTrue($result->success);
        $this->assertSame([], $result->errorCodes);
        $this->assertNull($result->hostname);
        $this->assertNull($result->action);
        $this->assertNull($result->challengeTs);
    }

    #[Test]
    public function failureWithAllFields(): void
    {
        $result = new VerificationResult(
            success: false,
            errorCodes: ['invalid-input-response'],
            hostname: 'example.com',
            action: 'login',
            challengeTs: '2026-01-01T00:00:00Z',
        );

        $this->assertFalse($result->success);
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
        $this->assertSame('example.com', $result->hostname);
        $this->assertSame('login', $result->action);
        $this->assertSame('2026-01-01T00:00:00Z', $result->challengeTs);
    }
}
