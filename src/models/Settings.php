<?php

namespace madebyraygun\componentlibrary\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    public array $aliases = [];

    public string $root = '';

    public string $docs = '';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->root = Craft::getAlias('@root') . '/library';
        $this->docs = Craft::getAlias('@root') . '/docs';
    }

    public function rules(): array
    {
        return [
            ['aliases', 'required'],
            ['aliases', 'array'],
        ];
    }
}
