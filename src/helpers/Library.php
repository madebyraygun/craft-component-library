<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\FileHelper;
use madebyraygun\componentlibrary\Plugin;

class Library
{
    public static function scanLibraryPath(): array
    {
        $settings = Plugin::$plugin->getSettings();
        $nodes = self::scanPath($settings->root);
        return [
            'name' => 'Components',
            'nodes' => $nodes,
        ];
    }

    public static function scanPath($path)
    {
        $directories = FileHelper::findDirectories($path, [
            'recursive' => false
        ]);

        // sort directories
        usort($directories, function ($a, $b) {
            return strcasecmp(basename($a), basename($b));
        });

        $files = FileHelper::findFiles($path, [
            'only' => ['*.twig'],
            'recursive' => false
        ]);

        $result = [];

        // Scan directories
        foreach ($directories as $directory) {
            $result[] = [
                'name' => basename($directory),
                'path' => $directory,
                'type' => 'directory',
                'nodes' => self::scanPath($directory)
            ];
        }

        // Add files
        foreach ($files as $file) {
            $handlePath = self::getComponentPath($file);
            $previewUrl = self::getComponentPreviewUrl($handlePath);
            $result[] = [
                'name' => basename($file),
                'extension' => pathinfo($file, PATHINFO_EXTENSION),
                'path' => $file,
                'handle' => $handlePath,
                'preview_url' => $previewUrl,
                'type' => 'file',
                'nodes' => []
            ];
        }

        return $result;
    }

    public static function getComponentPreviewUrl($handle)
    {
        $site = \Craft::$app->sites->getCurrentSite();
        $siteUrl = $site->getBaseUrl();
        return $siteUrl . '/component-library/preview?name=' . $handle;
    }

    public static function getComponentPath($path)
    {
        $settings = Plugin::$plugin->getSettings();
        $root = $settings->root;
        $path = str_replace($root . '/', '@', $path);
        return $path;
    }
}
