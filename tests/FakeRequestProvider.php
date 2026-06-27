<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;

/**
 * @internal
 */
final class FakeRequestProvider implements RequestProviderInterface
{
    private int $callCount = 0;

    public function __construct(
        private readonly ?ServerRequestInterface $request = null,
    ) {}

    public function callCount(): int
    {
        return $this->callCount;
    }

    #[\Override]
    public function set(ServerRequestInterface $request): void {}

    #[\Override]
    public function get(): ServerRequestInterface
    {
        $this->callCount++;

        if ($this->request === null) {
            throw new RequestNotSetException();
        }

        return $this->request;
    }
}
