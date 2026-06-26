<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Stringable;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @internal
 */
final class FakeTranslator implements TranslatorInterface
{
    private int $callCount = 0;

    public function __construct(
        private readonly string $translation = 'translated',
        private readonly string $locale = 'en',
    ) {}

    public function callCount(): int
    {
        return $this->callCount;
    }

    #[\Override]
    public function addCategorySources(CategorySource ...$categories): static
    {
        return $this;
    }

    #[\Override]
    public function setLocale(string $locale): static
    {
        return $this;
    }

    #[\Override]
    public function getLocale(): string
    {
        return $this->locale;
    }

    #[\Override]
    public function translate(
        string|Stringable $id,
        array $parameters = [],
        ?string $category = null,
        ?string $locale = null,
    ): string {
        $this->callCount++;

        return $this->translation;
    }

    #[\Override]
    public function withDefaultCategory(string $category): static
    {
        return $this;
    }

    #[\Override]
    public function withLocale(string $locale): static
    {
        return $this;
    }
}
