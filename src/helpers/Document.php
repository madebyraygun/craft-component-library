<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\StringHelper;

class Document
{
    private static array $cache = [];

    public static function parseDocumentParts(string $name): object
    {
        $name = strtolower($name);
        if (isset(Document::$cache[$name])) {
            return Document::$cache[$name];
        }

        $documentPath = Component::resolveFilePath($name, 'md');
        $fileInfo = pathinfo($documentPath);
        $result = (object)[
            'valid' => $fileInfo['extension'] === 'md' && file_exists($documentPath),
            'name' => $name,
            'docPath' => $documentPath,
        ];
        Document::$cache[$name] = $result;
        return $result;
    }

    public static function getDefaultPaths(string $templatePath): array
    {
        $result = [
            preg_replace('/\.twig/', '.md', $templatePath),
        ];
        $cannonicalName = Component::getDefaultName($templatePath);
        $names = [
            $cannonicalName,
            $cannonicalName . '.readme',
            'readme',
            'Readme',
            'README',
            'index',
        ];
        $pathInfo = pathinfo($templatePath);
        foreach ($names as $name) {
            $result[] = $pathInfo['dirname'] . '/' . $name . '.md';
        }
        return $result;
    }

    public static function getDocPartsFromTemplate(string $handle): object
    {
        $templatePath = Component::resolveFilePath($handle, 'twig');
        $paths = Document::getDefaultPaths($templatePath);
        foreach ($paths as $path) {
            $parts = self::parseDocumentParts($path);
            if ($parts->valid) {
                return $parts;
            }
        }
        return (object)[
            'valid' => false,
            'name' => 'Invalid Document',
            'docPath' => $templatePath,
        ];
    }

    public static function normalizeName(string $name): string
    {
        $name = strtolower($name);
        $name = StringHelper::dasherize($name);
        return $name;
    }
}
