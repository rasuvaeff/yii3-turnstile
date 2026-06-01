<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Yiisoft\Widget\WidgetFactory;

#[CoversClass(Turnstile::class)]
final class TurnstileTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        WidgetFactory::initialize();
    }

    #[Test]
    public function rendersScriptAndDiv(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringContainsString('src="https://challenges.cloudflare.com/turnstile/v0/api.js"', $html);
        $this->assertStringContainsString('async', $html);
        $this->assertStringContainsString('defer', $html);
        $this->assertStringContainsString('cf-turnstile', $html);
        $this->assertStringContainsString('data-sitekey="test-key"', $html);
    }

    #[Test]
    public function rendersThemeAttribute(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withTheme(TurnstileTheme::Dark)
            ->render();

        $this->assertStringContainsString('data-theme="dark"', $html);
    }

    #[Test]
    public function rendersSizeAttribute(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withSize(TurnstileSize::Compact)
            ->render();

        $this->assertStringContainsString('data-size="compact"', $html);
    }

    #[Test]
    public function rendersResponseFieldName(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('myCaptcha')
            ->render();

        $this->assertStringContainsString('data-response-field-name="myCaptcha"', $html);
    }

    #[Test]
    public function throwsWhenSiteKeyNotSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('siteKey is required');

        Turnstile::widget()->render();
    }

    #[Test]
    public function usesSiteKeyFromConfig(): void
    {
        $config = new TurnstileConfig(siteKey: 'config-key', secret: 'secret');
        $html = (new Turnstile(config: $config))->render();

        $this->assertStringContainsString('data-sitekey="config-key"', $html);
    }

    #[Test]
    public function withSiteKeyOverridesConfig(): void
    {
        $config = new TurnstileConfig(siteKey: 'config-key', secret: 'secret');
        $html = (new Turnstile(config: $config))->withSiteKey('override-key')->render();

        $this->assertStringContainsString('data-sitekey="override-key"', $html);
    }

    #[Test]
    public function customJsApiUrl(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withJsApiUrl('https://custom.test/api.js')
            ->render();

        $this->assertStringContainsString('src="https://custom.test/api.js"', $html);
    }

    #[Test]
    public function renderOutputHasScriptBeforeDiv(): void
    {
        $html = Turnstile::widget()->withSiteKey('key')->render();

        $scriptPos = strpos($html, '<script');
        $divPos = strpos($html, '<div');
        $this->assertNotFalse($scriptPos);
        $this->assertNotFalse($divPos);
        $this->assertLessThan($divPos, $scriptPos);
        $this->assertStringContainsString("</script>\n<div", $html);
    }

    #[Test]
    public function withSiteKeyDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('original');
        $new = $widget->withSiteKey('changed');

        $this->assertNotSame($widget, $new);
        $this->assertStringContainsString('data-sitekey="original"', $widget->render());
        $this->assertStringContainsString('data-sitekey="changed"', $new->render());
    }

    #[Test]
    public function withThemeDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withTheme(TurnstileTheme::Light);
        $new = $widget->withTheme(TurnstileTheme::Dark);

        $this->assertNotSame($widget, $new);
        $this->assertStringContainsString('data-theme="light"', $widget->render());
        $this->assertStringContainsString('data-theme="dark"', $new->render());
    }

    #[Test]
    public function withSizeDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withSize(TurnstileSize::Normal);
        $new = $widget->withSize(TurnstileSize::Compact);

        $this->assertNotSame($widget, $new);
        $this->assertStringContainsString('data-size="normal"', $widget->render());
        $this->assertStringContainsString('data-size="compact"', $new->render());
    }

    #[Test]
    public function withResponseFieldNameDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withResponseFieldName('original-name');
        $new = $widget->withResponseFieldName('changed-name');

        $this->assertNotSame($widget, $new);
        $this->assertStringContainsString('data-response-field-name="original-name"', $widget->render());
        $this->assertStringContainsString('data-response-field-name="changed-name"', $new->render());
    }

    #[Test]
    public function withJsApiUrlDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withJsApiUrl('https://original.test/api.js');
        $new = $widget->withJsApiUrl('https://changed.test/api.js');

        $this->assertNotSame($widget, $new);
        $this->assertStringContainsString('src="https://original.test/api.js"', $widget->render());
        $this->assertStringContainsString('src="https://changed.test/api.js"', $new->render());
    }
}
