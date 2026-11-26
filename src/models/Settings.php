<?php

namespace madebyraygun\componentlibrary\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public array $aliases = [];

    public array $browser = [
        'requiresLogin' => true,
        'enabled' => true,
        'path' => 'component-library',
        'welcome' => '',
        'preview' => '@preview',
    ];

    public string $root = '';

    public string $docs = '';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->root = Craft::getAlias('@root') . '/library';
        $this->docs = Craft::getAlias('@root') . '/docs';
        $this->browser = [];
    }

    /**
     * Whether the library browser asks guests to log in. A login is required unless
     * `browser.requiresLogin` is set to `false`.
     */
    public function browserRequiresLogin(): bool
    {
        return (bool)($this->browser['requiresLogin'] ?? true);
    }

    /**
     * Whether the library browser is served at all. When this is false every browser and
     * preview request is a 404, whichever URL it arrives on.
     */
    public function browserEnabled(): bool
    {
        return (bool)($this->browser['enabled'] ?? true);
    }

    public function browserPath(): string
    {
        return $this->browser['path'] ?? 'component-library';
    }

    public function browserWelcome(): string
    {
        return $this->browser['welcome'] ?? '';
    }

    public function browserPreview(): string
    {
        return $this->browser['preview'] ?? '@preview';
    }

    public function rules(): array
    {
        return [
            ['aliases', 'required'],
            ['aliases', 'array'],
        ];
    }
}
