<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\StringHelper;

class Document
{
    private static array $cache = [];

    public static function parseDocumentParts(string $name): object
    {
        if (!Common::isUsableHandle($name)) {
            return self::outsideParts($name);
        }
        $name = Common::collapseHandlePath($name);
        if (isset(Document::$cache[$name])) {
            return Document::$cache[$name];
        }
        if (!Common::isHandleInsideLibrary($name)) {
            return Document::$cache[$name] = self::outsideParts($name);
        }
        $friendlyName = Common::friendlyNameFromHandle($name);
        $friendlyName = preg_replace('/^index$/i', 'Overview', $friendlyName);
        $documentPath = Common::resolveHandlePath($name, 'md');
        $fileInfo = pathinfo($documentPath);
        $result = (object)[
            'type' => 'document',
            'icon' => 'article',
            'valid' => ($fileInfo['extension'] ?? '') === 'md' && Common::isFileInsideLibrary($documentPath),
            'name' => $friendlyName,
            'includeName' => $name,
            'docPath' => $documentPath,
        ];
        Document::$cache[$name] = $result;
        return $result;
    }

    /**
     * The parts of a handle that does not resolve to a file inside the library or the docs folder.
     */
    private static function outsideParts(string $name): object
    {
        return (object)[
            'type' => 'document',
            'icon' => 'article',
            'valid' => false,
            'name' => '',
            'includeName' => $name,
            'docPath' => '',
        ];
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
        if (!Common::isHandleInsideLibrary($handle)) {
            return (object)[
                'valid' => false,
                'name' => 'Invalid Document',
                'docPath' => '',
            ];
        }
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
