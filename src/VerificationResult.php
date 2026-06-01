<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

/**
 * @api
 */
final readonly class VerificationResult
{
    /**
     * @param string[] $errorCodes
     */
    public function __construct(
        public bool $success,
        public array $errorCodes = [],
        public ?string $hostname = null,
        public ?string $action = null,
        public ?string $challengeTs = null,
    ) {}
}
