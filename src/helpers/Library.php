<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\helpers\Json;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\helpers\Component;
use madebyraygun\componentlibrary\helpers\Loader;
use Craft;

class Library
{
    public static function scanLibraryPath(): array
    {
        $settings = Plugin::$plugin->getSettings();
        $name = Craft::$app->request->getParam('name');
        $nodes = self::scanPath($settings->root, $name);
        return [
            'name' => 'Components',
            'nodes' => $nodes,
            'hidden' => false,
            'level' => 0,
        ];
    }

    public static function getCurrentTemplatePath(): string
    {

        if (empty($name)) return '';
        $parts = Component::parseComponentParts($name);
        return $parts->templatePath;
    }

    public static function scanPath(string $path, string $currentName = '', int $level = 1): array
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
            $nodes = self::scanPath($directory, $currentName, $level + 1);
            $hidden = self::allNodesHidden($nodes);
            $hasActiveChild = self::hasActiveChild($nodes);
            $result[] = [
                'name' => basename($directory),
                'path' => $directory,
                'level' => $level,
                'type' => 'directory',
                'hidden' => $hidden,
                'expanded' => $hasActiveChild,
                'nodes' => $nodes
            ];
        }

        // Add files
        foreach ($files as $file) {
            $handlePath = self::getComponentPath($file);
            $result[] = self::formatFileEntry($handlePath, $currentName);
            $variants = Context::getVariants($handlePath, true);
            if (!empty($variants))
            {
                foreach ($variants as $variant) {
                    $result[] = self::formatFileEntry($variant->includeName, $currentName);
                }
            }
        }

        return $result;
    }

    public static function formatFileEntry(string $handlePath, string $currentName): array {
        $component = Component::parseComponentParts($handlePath);
        $context = Context::parseConfigParts($handlePath);
        $pagePreviewUrl = self::getPagePreviewUrl($handlePath);
        $partialToolbarUrl = self::getPartialUrl($handlePath, 'toolbar');
        $partialPreviewUrl = self::getPartialUrl($handlePath, 'preview');
        $isolatedPreviewUrl = self::getIsolatedPreviewUrl($handlePath);
        return [
            'name' => $context->settings->title,
            'hidden' => $context->settings->hidden,
            'current' => $currentName == $handlePath,
            'path' => $component->templatePath,
            'handle' => $handlePath,
            'page_url' => $pagePreviewUrl,
            'partial_toolbar_url' => $partialToolbarUrl,
            'partial_preview_url' => $partialPreviewUrl,
            'isolated_url' => $isolatedPreviewUrl,
            'type' => 'file',
            'is_variant' => $component->isVariant,
            'is_virtual' => $component->isVirtual,
            'nodes' => []
        ];
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

    public static function allNodesHidden(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if (!$node['hidden']) return false;
        }
        return true;
    }

    public static function getPagePreviewUrl(string $handle): string
    {
        $siteUrl = UrlHelper::siteUrl('/component-library');
        return UrlHelper::urlWithParams($siteUrl, ['name' => $handle]);
    }

    public static function getPartialUrl(string $handle, string $partial): string
    {
        $siteUrl = UrlHelper::siteUrl('/component-library/partials/' . $partial);
        return UrlHelper::urlWithParams($siteUrl, ['name' => $handle]);
    }

    public static function getIsolatedPreviewUrl(string|null $handle): string
    {
        $template = empty($handle) ? 'welcome' : 'preview';
        if (!empty($handle) && !Loader::componentExists($handle)) {
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

    public static function getContextContents(string $path, bool $compiled): string {
        $parts = Component::parseComponentParts($path);
        if (!$parts->configExists) return '';
        $contextPath = $parts->configPath;
        if ($compiled) {
            $ctx = Context::getComponentContext($path);
            return Json::encode($ctx, JSON_PRETTY_PRINT);
        }
        return file_get_contents($contextPath);
    }

    public static function getComponentContents(string $handle, bool $compiled): string {
        $parts = Component::parseComponentParts($handle);
        if ($compiled) {
            $context = Context::getComponentContext($handle);
            try
            {
                $view = Craft::$app->getView();
                return $view->renderTemplate($handle, $context);
            } catch (\Throwable $e)
            {
                return 'Error: ' . $e->getMessage();
            }
        }
        return file_get_contents($parts->templatePath);
    }

    public static function getUiToolbarContext(string $name): array {
        if (empty($name)) {
            return [
                'error' => 'No name parameter provided'
            ];
        }

        $exists = Loader::componentExists($name);
        if (!$exists) {
            return [
                'error' => 'Component does not exist'
            ];
        }

        $component = Component::parseComponentParts($name);
        $context = Context::parseConfigParts($name);
        return [
            'name' => $name,
            'component' => $component,
            'context' => $context,
            'contents' => [
                'compiled_context' => Library::getContextContents($name, true),
                'compiled_component' => Library::getComponentContents($name, true),
                'raw_context' => Library::getContextContents($name, false),
                'raw_component' => Library::getComponentContents($name, false),
            ]
        ];
    }
}
