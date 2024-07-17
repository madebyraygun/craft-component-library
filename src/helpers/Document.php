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

        $friendlyName = Common::friendlyNameFromHandle($name);
        $friendlyName = preg_replace('/^index$/i', 'Overview', $friendlyName);
        $documentPath = Common::resolveHandlePath($name, 'md');
        $fileInfo = pathinfo($documentPath);
        $result = (object)[
            'valid' => $fileInfo['extension'] === 'md' && file_exists($documentPath),
            'name' => $friendlyName,
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
        $cannonicalName = Component::getCanonicalName($templatePath);
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
        $templatePath = Common::resolveHandlePath($handle, 'twig');
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
}
