<?php

namespace madebyraygun\componentlibrary\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

class Settings extends Model
{
    /**
     * The value of each `browser` setting that is not set, or is set to `null`.
     */
    public const BROWSER_DEFAULTS = [
        'enabled' => true,
        'requiresLogin' => true,
        'path' => 'component-library',
        'welcome' => '',
        'preview' => '@preview',
    ];

    public array $aliases = [];

    public array $browser = [];

    public string $root = '';

    public string $docs = '';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->root = Craft::getAlias('@root') . '/library';
        $this->docs = Craft::getAlias('@root') . '/docs';
    }

    /**
     * The `browser.requiresLogin` setting: whether guests must log in to use the library browser.
     */
    public function browserRequiresLogin(): bool
    {
        return $this->browserFlag('requiresLogin');
    }

    /**
     * The `browser.enabled` setting: whether the library browser is enabled.
     */
    public function browserEnabled(): bool
    {
        return $this->browserFlag('enabled');
    }

    public function browserPath(): string
    {
        return $this->browser['path'] ?? self::BROWSER_DEFAULTS['path'];
    }

    public function browserWelcome(): string
    {
        return $this->browser['welcome'] ?? self::BROWSER_DEFAULTS['welcome'];
    }

    public function browserPreview(): string
    {
        return $this->browser['preview'] ?? self::BROWSER_DEFAULTS['preview'];
    }

    /**
     * A boolean `browser` setting. A string is read the way Craft reads a boolean environment value,
     * so `'false'`, `'off'` and `'0'` are false and a `$VARIABLE` reference is resolved first.
     * A value that is not set, is `null`, or is a string with no boolean meaning is the default.
     */
    private function browserFlag(string $key): bool
    {
        $value = $this->browser[$key] ?? null;
        if (is_string($value)) {
            $value = App::parseBooleanEnv(App::parseEnv($value));
        }
        return $value === null ? self::BROWSER_DEFAULTS[$key] : (bool)$value;
    }

    public function rules(): array
    {
        return [
            ['aliases', 'required'],
            ['aliases', 'array'],
        ];
    }
}
