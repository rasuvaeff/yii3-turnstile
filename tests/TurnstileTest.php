<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile\Tests;

use Rasuvaeff\Yii3Turnstile\Turnstile;
use Rasuvaeff\Yii3Turnstile\TurnstileConfig;
use Rasuvaeff\Yii3Turnstile\TurnstileSize;
use Rasuvaeff\Yii3Turnstile\TurnstileTheme;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Widget\WidgetFactory;

#[Test]
#[Covers(Turnstile::class)]
final class TurnstileTest
{
    #[BeforeTest]
    public function setUp(): void
    {
        WidgetFactory::initialize();
    }

    public function rendersScriptAndDiv(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('test-key')
            ->render();

        Assert::string($html)->contains('src="https://challenges.cloudflare.com/turnstile/v0/api.js"');
        Assert::string($html)->contains('async');
        Assert::string($html)->contains('defer');
        Assert::string($html)->contains('cf-turnstile');
        Assert::string($html)->contains('data-sitekey="test-key"');
    }

    public function rendersThemeAttribute(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withTheme(TurnstileTheme::Dark)
            ->render();

        Assert::string($html)->contains('data-theme="dark"');
    }

    public function rendersSizeAttribute(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withSize(TurnstileSize::Compact)
            ->render();

        Assert::string($html)->contains('data-size="compact"');
    }

    public function rendersInvisibleSizeAttribute(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withSize(TurnstileSize::Invisible)
            ->render();

        Assert::string($html)->contains('data-size="invisible"');
    }

    public function rendersResponseFieldName(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('myCaptcha')
            ->render();

        Assert::string($html)->contains('data-response-field-name="myCaptcha"');
    }

    public function throwsWhenSiteKeyNotSet(): void
    {
        try {
            Turnstile::widget()->render();
            Assert::fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            Assert::string($e->getMessage())->contains('siteKey is required');
        }
    }

    public function emptySiteKeyInConfigIsTreatedAsUnset(): void
    {
        $config = new TurnstileConfig(siteKey: '', secret: 'secret');

        try {
            (new Turnstile(config: $config))->render();
            Assert::fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            Assert::string($e->getMessage())->contains('siteKey is required');
        }
    }

    public function usesSiteKeyFromConfig(): void
    {
        $config = new TurnstileConfig(siteKey: 'config-key', secret: 'secret');
        $html = (new Turnstile(config: $config))->render();

        Assert::string($html)->contains('data-sitekey="config-key"');
    }

    public function withSiteKeyOverridesConfig(): void
    {
        $config = new TurnstileConfig(siteKey: 'config-key', secret: 'secret');
        $html = (new Turnstile(config: $config))->withSiteKey('override-key')->render();

        Assert::string($html)->contains('data-sitekey="override-key"');
    }

    public function customJsApiUrl(): void
    {
        $html = Turnstile::widget()
            ->withSiteKey('key')
            ->withJsApiUrl('https://custom.test/api.js')
            ->render();

        Assert::string($html)->contains('src="https://custom.test/api.js"');
    }

    public function renderOutputHasScriptBeforeDiv(): void
    {
        $html = Turnstile::widget()->withSiteKey('key')->render();

        $scriptPos = strpos($html, '<script');
        $divPos = strpos($html, '<div');
        Assert::notSame($scriptPos, false);
        Assert::notSame($divPos, false);
        Assert::true($scriptPos < $divPos);
        Assert::string($html)->contains("</script>\n<div");
    }

    public function withSiteKeyDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('original');
        $new = $widget->withSiteKey('changed');

        Assert::notSame($widget, $new);
        Assert::string($widget->render())->contains('data-sitekey="original"');
        Assert::string($new->render())->contains('data-sitekey="changed"');
    }

    public function withThemeDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withTheme(TurnstileTheme::Light);
        $new = $widget->withTheme(TurnstileTheme::Dark);

        Assert::notSame($widget, $new);
        Assert::string($widget->render())->contains('data-theme="light"');
        Assert::string($new->render())->contains('data-theme="dark"');
    }

    public function withSizeDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withSize(TurnstileSize::Normal);
        $new = $widget->withSize(TurnstileSize::Compact);

        Assert::notSame($widget, $new);
        Assert::string($widget->render())->contains('data-size="normal"');
        Assert::string($new->render())->contains('data-size="compact"');
    }

    public function withSizeInvisibleDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withSize(TurnstileSize::Normal);
        $new = $widget->withSize(TurnstileSize::Invisible);

        Assert::notSame($widget, $new);
        Assert::string($widget->render())->contains('data-size="normal"');
        Assert::string($new->render())->contains('data-size="invisible"');
    }

    public function withResponseFieldNameDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withResponseFieldName('original-name');
        $new = $widget->withResponseFieldName('changed-name');

        Assert::notSame($widget, $new);
        Assert::string($widget->render())->contains('data-response-field-name="original-name"');
        Assert::string($new->render())->contains('data-response-field-name="changed-name"');
    }

    public function withJsApiUrlDoesNotMutateOriginalInstance(): void
    {
        $widget = Turnstile::widget()->withSiteKey('key')->withJsApiUrl('https://original.test/api.js');
        $new = $widget->withJsApiUrl('https://changed.test/api.js');

        Assert::notSame($widget, $new);
        Assert::string($widget->render())->contains('src="https://original.test/api.js"');
        Assert::string($new->render())->contains('src="https://changed.test/api.js"');
    }
}
