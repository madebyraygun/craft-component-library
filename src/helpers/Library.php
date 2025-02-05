<?php

namespace madebyraygun\componentlibrary\helpers;

use Craft;
use craft\helpers\Json;
use craft\helpers\FileHelper;
use madebyraygun\componentlibrary\Plugin;

class Library
{
    public static function getLibraryTree(): array
    {
        $settings = Plugin::$plugin->getSettings();
        $name = Craft::$app->request->getParam('name');
        $nodes = self::scanPath($settings->root, $name ?? '', 1, [
            'dirs' => self::getFilterFunction([ $settings->docs ]),
            'files' => ['*.twig'],
        ]);
        return [
            'name' => 'Components',
            'nodes' => $nodes,
            'hidden' => false,
            'level' => 0,
        ];
    }

    public static function getDocumentsTree(): array
    {
        $name = Craft::$app->request->getParam('name');
        $settings = Plugin::$plugin->getSettings();
        $nodes = self::scanPath($settings->docs, $name ?? '', 1, [
            'dirs' => null,
            'files' => ['*.md'],
        ]);
        return [
            'name' => 'Documentation',
            'nodes' => $nodes,
            'hidden' => false,
            'level' => 0,
        ];
    }


    public static function getSearchIndexFromTrees(array $trees): array
    {
        // Flatten trees into a list of nodes with their path and name
        $nodes = [];
        foreach ($trees as $tree) {
            $nodes = array_merge($nodes, self::flattenTree($tree));
        }
        return $nodes;
    }

    public static function flattenTree(array $tree): array
    {
        $nodes = [];
        foreach ($tree['nodes'] as $node) {
            if ($node['type'] !== 'directory') {
                $nodes[] = [
                    'name' => $node['name'],
                    'includeName' => $node['includeName'],
                    'type' => $node['type'],
                    'icon' => $node['icon'] ?? '',
                    'path' => $node['path'],
                ];
            }
            $nodes = array_merge($nodes, self::flattenTree($node));
        }
        return $nodes;
    }

    public static function getFilterFunction(array $filter): callable
    {
        return function($path) use ($filter) {
            foreach ($filter as $f) {
                if (strpos($path, $f) !== false) {
                    return false;
                }
            }
            return true;
        };
    }

    public static function scanPath(string $path, string $currentName = '', int $level = 1, $filter = null): array
    {
        // dir exists?
        if (!is_dir($path)) {
            return [];
        }

        $directories = FileHelper::findDirectories($path, [
            'recursive' => false,
            'filter' => $filter['dirs'],
        ]);

        // sort directories
        usort($directories, function($a, $b) {
            return strcasecmp(basename($a), basename($b));
        });

        $files = FileHelper::findFiles($path, [
            'only' => $filter['files'],
            'recursive' => false,
        ]);

        $result = [];

        // Scan directories
        foreach ($directories as $directory) {
            $nodes = self::scanPath($directory, $currentName, $level + 1, $filter);
            $hidden = self::allNodesHidden($nodes);
            $hasActiveChild = self::hasActiveChild($nodes);
            $result[] = [
                'name' => basename($directory),
                'path' => $directory,
                'level' => $level,
                'type' => 'directory',
                'hidden' => $hidden,
                'expanded' => $hasActiveChild,
                'nodes' => $nodes,
            ];
        }

        // Add files
        foreach ($files as $file) {
            $handlePath = self::getComponentPath($file);
            $result[] = self::formatFileEntry($handlePath, $currentName);
            if (Loader::componentExists($handlePath)) {
                $variants = Context::getVariants($handlePath, true);
                if (!empty($variants)) {
                    foreach ($variants as $variant) {
                        $result[] = self::formatFileEntry($variant->includeName, $currentName);
                    }
                }
            }
        }

        return $result;
    }

    public static function formatFileEntry(string $handlePath, string $currentName): array
    {
        $handlePath = Common::collapseHandlePath($handlePath);
        $fields = [];
        if (Loader::componentExists($handlePath)) {
            $component = Component::parseComponentParts($handlePath);
            $context = Context::parseConfigParts($handlePath);
            $fields = [
                'type' => $component->type,
                'icon' => $component->icon,
                'name' => $context->settings->title,
                'hidden' => $context->settings->hidden,
                'path' => $component->templatePath,
                'includeName' => $component->includeName,
                'context' => $context,
                'partial_toolbar_url' => self::getPartialUrl($handlePath, 'toolbar'),
                'is_variant' => $component->isVariant,
                'is_virtual' => $component->isVirtual,
            ];
        }
        if (Loader::documentExists($handlePath)) {
            $document = Document::parseDocumentParts($handlePath);
            $fields = [
                'type' => $document->type,
                'icon' => $document->icon,
                'name' => $document->name,
                'path' => $document->docPath,
                'includeName' => $document->includeName,
                'hidden' => false,
                'partial_toolbar_url' => null,
                'context' => null,
            ];
        }
        $pagePreviewUrl = self::getPagePreviewUrl($handlePath);
        $partialPreviewUrl = self::getPartialUrl($handlePath, 'preview');
        $isolatedPreviewUrl = self::getIsolatedPreviewUrl($handlePath);
        return [
            ...$fields,
            'current' => $currentName == $handlePath,
            'page_url' => $pagePreviewUrl,
            'partial_preview_url' => $partialPreviewUrl,
            'isolated_url' => $isolatedPreviewUrl,
            'nodes' => [],
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
            if (!$node['hidden']) {
                return false;
            }
        }
        return true;
    }

    public static function getPagePreviewUrl(string $handle): string
    {
        return Common::libraryUrl('/', ['name' => $handle]);
    }

    public static function getPartialUrl(string $handle, string $partial): string
    {
        return Common::libraryUrl('partials/' . $partial, ['name' => $handle]);
    }

    public static function getIsolatedPreviewUrl(string|null $handle): string
    {
        return Common::libraryUrl('preview', ['name' => $handle]);
    }

    public static function getComponentPath(string $path): string
    {
        $settings = Plugin::$plugin->getSettings();
        $root = $settings->root;
        $path = str_replace($root . '/', '@', $path);
        return $path;
    }

    public static function getContextContents(string $path, bool $compiled): string
    {
        $parts = Component::parseComponentParts($path);
        if (!$parts->configExists) {
            return '';
        }
        $contextPath = $parts->configPath;
        if ($compiled) {
            $out = (array)Context::parseConfigParts($path);
            // merge settings key contents to the top level
            $out = array_merge((array)$out['settings'], $out);
            unset($out['settings']);
            return Json::encode($out, JSON_PRETTY_PRINT);
        }
        return file_get_contents($contextPath);
    }

    public static function getDocContents(string $handle): string
    {
        $parts = Document::getDocPartsFromTemplate($handle);
        if (!$parts->valid) {
            return '';
        }
        return file_get_contents($parts->docPath);
    }

    public static function getComponentContents(string $handle, bool $compiled): string
    {
        $parts = Component::parseComponentParts($handle);
        if ($compiled) {
            $context = Context::getComponentContext($handle);
            try {
                $view = Craft::$app->getView();
                return $view->renderTemplate($handle, $context);
            } catch (\Throwable $e) {
                return 'Error: ' . $e->getMessage();
            }
        }
        return file_get_contents($parts->templatePath);
    }

    public static function getUiToolbarContext(string $name): array|null
    {
        if (empty($name)) {
            return null;
        }

        $exists = Loader::componentExists($name);
        if (!$exists) {
            return null;
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
                'doc_contents' => Library::getDocContents($name),
            ],
        ];
    }
}
