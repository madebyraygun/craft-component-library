<?php

namespace madebyraygun\componentlibrary\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public array $aliases = [];

    public array $browser = [
        'requiresLogin' => false,
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

    public function browserRequiresLogin(): bool
    {
        return $this->browser['requiresLogin'] ?? false === false;
    }

    public function browserEnabled(): bool
    {
        return $this->browser['enabled'] ?? true === true;
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
