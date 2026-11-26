<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use madebyraygun\componentlibrary\models\Settings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    private const UNSET = '__unset__';

    protected function setUp(): void
    {
        $_SERVER['COMPONENT_LIBRARY_TEST_FALSE'] = 'false';
        $_SERVER['COMPONENT_LIBRARY_TEST_TRUE'] = 'true';
        unset($_SERVER['COMPONENT_LIBRARY_TEST_MISSING']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['COMPONENT_LIBRARY_TEST_FALSE'], $_SERVER['COMPONENT_LIBRARY_TEST_TRUE']);
    }

    /**
     * What a value means for `browser.enabled` and for `browser.requiresLogin`. `null` stands for
     * "the default", so the rows hold whatever BROWSER_DEFAULTS says.
     *
     * @return array<string, array{mixed, bool|null}>
     */
    public static function values(): array
    {
        return [
            'unset' => [self::UNSET, null],
            'null' => [null, null],
            'true' => [true, true],
            'false' => [false, false],
            'int 1' => [1, true],
            'int 0' => [0, false],
            "string 'true'" => ['true', true],
            "string 'false'" => ['false', false],
            "string '1'" => ['1', true],
            "string '0'" => ['0', false],
            "string 'on'" => ['on', true],
            "string 'off'" => ['off', false],
            "string 'yes'" => ['yes', true],
            "string 'no'" => ['no', false],
            "string 'FALSE'" => ['FALSE', false],
            'empty string' => ['', false],
            'string with no boolean meaning' => ['sometimes', null],
            'environment variable holding false' => ['$COMPONENT_LIBRARY_TEST_FALSE', false],
            'environment variable holding true' => ['$COMPONENT_LIBRARY_TEST_TRUE', true],
            'environment variable that is not set' => ['$COMPONENT_LIBRARY_TEST_MISSING', null],
        ];
    }

    #[DataProvider('values')]
    public function testBrowserEnabled(mixed $value, ?bool $expected): void
    {
        $settings = $this->settings('enabled', $value);

        self::assertSame($expected ?? Settings::BROWSER_DEFAULTS['enabled'], $settings->browserEnabled());
    }

    #[DataProvider('values')]
    public function testBrowserRequiresLogin(mixed $value, ?bool $expected): void
    {
        $settings = $this->settings('requiresLogin', $value);

        self::assertSame($expected ?? Settings::BROWSER_DEFAULTS['requiresLogin'], $settings->browserRequiresLogin());
    }

    public function testTheDefaultsAreDefinedInOnePlace(): void
    {
        self::assertSame([
            'enabled' => true,
            'requiresLogin' => true,
            'path' => 'component-library',
            'welcome' => '',
            'preview' => '@preview',
        ], Settings::BROWSER_DEFAULTS);
    }

    public function testASettingsModelWithNoConfigUsesEveryDefault(): void
    {
        $settings = new Settings();

        self::assertSame([], $settings->browser);
        self::assertSame(Settings::BROWSER_DEFAULTS, [
            'enabled' => $settings->browserEnabled(),
            'requiresLogin' => $settings->browserRequiresLogin(),
            'path' => $settings->browserPath(),
            'welcome' => $settings->browserWelcome(),
            'preview' => $settings->browserPreview(),
        ]);
    }

    public function testSettingsPassedToTheConstructorAreKept(): void
    {
        $settings = new Settings(['browser' => ['enabled' => false, 'path' => 'library']]);

        self::assertFalse($settings->browserEnabled());
        self::assertSame('library', $settings->browserPath());
        self::assertTrue($settings->browserRequiresLogin());
    }

    /**
     * The expression `!App::env('CRAFT_ENVIRONMENT') === 'dev'` is always false, in every environment.
     */
    public function testTheNegatedComparisonNeverRequiresALogin(): void
    {
        foreach (['dev', 'staging', 'production'] as $environment) {
            /** @phpstan-ignore-next-line */
            $broken = !$environment === 'dev';
            self::assertFalse($this->settings('requiresLogin', $broken)->browserRequiresLogin());
            self::assertSame($environment !== 'dev', $this->settings('requiresLogin', $environment !== 'dev')->browserRequiresLogin());
        }
    }

    private function settings(string $key, mixed $value): Settings
    {
        $settings = new Settings();
        $settings->browser = $value === self::UNSET ? [] : [$key => $value];

        return $settings;
    }
}
