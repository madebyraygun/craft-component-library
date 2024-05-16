<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\helpers\Component;
use Craft;

class Library
{
    public static function scanLibraryPath(): array
    {
        $settings = Plugin::$plugin->getSettings();
        $name = Craft::$app->request->getParam('name');
        $parts = Component::parseComponentParts($name);
        $nodes = self::scanPath($settings->root, $parts->templatePath);
        return [
            'name' => 'Components',
            'nodes' => $nodes,
            'level' => 0,
        ];
    }

    public static function scanPath(string $path, string $currentPath = '', int $level = 1): array
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
            $nodes = self::scanPath($directory, $currentPath, $level + 1);
            $hasActiveChild = self::hasActiveChild($nodes);
            $result[] = [
                'name' => basename($directory),
                'path' => $directory,
                'level' => $level,
                'type' => 'directory',
                'expanded' => $hasActiveChild,
                'nodes' => $nodes
            ];
        }

        // Add files
        foreach ($files as $file) {
            $handlePath = self::getComponentPath($file);
            $previewUrl = self::getLandingPreviewUrl($handlePath);
            $result[] = [
                'name' => basename($file),
                'extension' => pathinfo($file, PATHINFO_EXTENSION),
                'current' => $file === $currentPath,
                'path' => $file,
                'handle' => $handlePath,
                'preview_url' => $previewUrl,
                'type' => 'file',
                'nodes' => []
            ];
        }

        return $result;
    }

    public static function hasActiveChild(array $nodes): bool
    {
        foreach ($nodes as $node) {
            $isCurrent = $node['current'] ?? false;
            if ($isCurrent || self::hasActiveChild($node['nodes'])) {
                return true;
            }
        }
        return false;
    }

    public static function getComponentPreviewUrl(string $handle): string
    {
        $siteUrl = UrlHelper::siteUrl('/component-library');
        return UrlHelper::urlWithParams($siteUrl, ['name' => $handle]);
    }

    public static function getLandingPreviewUrl(string $handle): string
    {
        $template = empty($handle) ? 'welcome' : 'preview';
        if (!Component::componentExists($handle)) {
            $template = 'not-found';
        }
        $siteUrl = UrlHelper::siteUrl('/component-library/' . $template);
        return UrlHelper::urlWithParams($siteUrl, ['name' => $handle]);
    }

    public static function getComponentPath(string $path): string
    {
        $settings = Plugin::$plugin->getSettings();
        $root = $settings->root;
        $path = str_replace($root . '/', '@', $path);
        return $path;
    }
}
