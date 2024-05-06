<?php

namespace madebyraygun\componentlibrary\models;

use craft\base\Model;
use Craft;

class Settings extends Model
{
    public array $aliases = [];

    public string $root = '';

    // set defaults
    public function init(): void
    {
        parent::init();
        $this->root = Craft::getAlias('@root') . '/library';
    }

    public function rules(): array
    {
        return [
            ['aliases', 'required'],
            ['aliases', 'array'],
        ];
    }
}