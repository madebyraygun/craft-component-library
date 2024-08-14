<?php

namespace madebyraygun\componentlibrary\helpers;

class Loader
{
    public static function handleExists(string $handle): bool
    {
        return self::componentExists($handle) || self::documentExists($handle);
    }

    public static function handleType(string $handle): string
    {
        if (self::handleExists($handle)) {
            return self::componentExists($handle) ? 'component' : 'document';
        }
        return '';
    }

    public static function parseHandleParts(string $handle): object
    {
        if (self::componentExists($handle)) {
            return Component::parseComponentParts($handle);
        }
        if (self::documentExists($handle)) {
            return Document::parseDocumentParts($handle);
        }
        return (object)[
            'includeName' => $handle,
            'valid' => false,
            'type' => '',
            'icon' => '',
            'name' => '',
        ];
    }

    public static function componentExists(string $name): bool
    {
        $parts = Component::parseComponentParts($name);
        if (!$parts->valid) {
            return false;
        }
        if ($parts->isVirtual) {
            $parentName = strstr($name, '--', true);
            $config = Context::readConfigFile($parentName);
            $variant = Context::getVariantInConfig($config, $parts->name);
            if (empty($variant)) {
                return false;
            }
        }
        return file_exists($parts->templatePath);
    }

    public static function documentExists(string $handle): bool
    {
        $parts = Document::parseDocumentParts($handle);
        if (!$parts->valid) {
            return false;
        }
        return file_exists($parts->docPath);
    }
}
