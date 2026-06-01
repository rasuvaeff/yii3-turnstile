<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

use Yiisoft\Html\Html;
use Yiisoft\Widget\Widget;

/**
 * @api
 */
final class Turnstile extends Widget
{
    private const string JS_API_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    private ?string $siteKey = null;
    private TurnstileTheme $theme = TurnstileTheme::Auto;
    private TurnstileSize $size = TurnstileSize::Normal;
    private string $responseFieldName = 'cf-turnstile-response';
    private string $jsApiUrl = self::JS_API_URL;

    public function __construct(
        ?TurnstileConfig $config = null,
    ) {
        if ($config !== null && $config->siteKey !== '') {
            $this->siteKey = $config->siteKey;
        }
    }

    public function withSiteKey(string $siteKey): self
    {
        $new = clone $this;
        $new->siteKey = $siteKey;

        return $new;
    }

    public function withTheme(TurnstileTheme $theme): self
    {
        $new = clone $this;
        $new->theme = $theme;

        return $new;
    }

    public function withSize(TurnstileSize $size): self
    {
        $new = clone $this;
        $new->size = $size;

        return $new;
    }

    public function withResponseFieldName(string $name): self
    {
        $new = clone $this;
        $new->responseFieldName = $name;

        return $new;
    }

    public function withJsApiUrl(string $url): self
    {
        $new = clone $this;
        $new->jsApiUrl = $url;

        return $new;
    }

    #[\Override]
    public function render(): string
    {
        $siteKey = $this->siteKey ?? throw new \RuntimeException('siteKey is required');

        $script = Html::script('')
            ->url($this->jsApiUrl)
            ->attribute('async', '')
            ->attribute('defer', '')
            ->render();

        $div = Html::div('')
            ->addClass('cf-turnstile')
            ->attribute('data-sitekey', $siteKey)
            ->attribute('data-response-field-name', $this->responseFieldName)
            ->attribute('data-theme', $this->theme->value)
            ->attribute('data-size', $this->size->value)
            ->render();

        return $script . "\n" . $div;
    }
}
