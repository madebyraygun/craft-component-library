<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use madebyraygun\componentlibrary\models\Settings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function enabledValues(): array
    {
        return [
            'not set' => [[], true],
            'true' => [['enabled' => true], true],
            'false' => [['enabled' => false], false],
            'null, as from an unset environment variable' => [['enabled' => null], true],
        ];
    }

    /**
     * @param array<string, mixed> $browser
     */
    #[DataProvider('enabledValues')]
    public function testBrowserEnabled(array $browser, bool $expected): void
    {
        self::assertSame($expected, $this->settings($browser)->browserEnabled());
    }

    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function requiresLoginValues(): array
    {
        return [
            'not set' => [[], true],
            'true' => [['requiresLogin' => true], true],
            'false' => [['requiresLogin' => false], false],
            'null, as from an unset environment variable' => [['requiresLogin' => null], true],
        ];
    }

    /**
     * @param array<string, mixed> $browser
     */
    #[DataProvider('requiresLoginValues')]
    public function testBrowserRequiresLogin(array $browser, bool $expected): void
    {
        self::assertSame($expected, $this->settings($browser)->browserRequiresLogin());
    }

    public function testASettingsModelWithNoConfigIsEnabledAndBehindALogin(): void
    {
        $settings = new Settings();

        self::assertTrue($settings->browserEnabled());
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
            self::assertFalse($this->settings(['requiresLogin' => $broken])->browserRequiresLogin());
            self::assertSame($environment !== 'dev', $this->settings(['requiresLogin' => $environment !== 'dev'])->browserRequiresLogin());
        }
    }

    /**
     * @param array<string, mixed> $browser
     */
    private function settings(array $browser): Settings
    {
        $settings = new Settings();
        $settings->browser = $browser;

        return $settings;
    }
}
