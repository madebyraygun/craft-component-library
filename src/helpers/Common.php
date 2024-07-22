<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use madebyraygun\componentlibrary\Plugin;

class Common
{
    public static function normalizeName(string $name): string
    {
        $name = strtolower($name);
        $name = StringHelper::dasherize($name);
        return $name;
    }

    public static function libraryUrl(string $path = '', array|string|null $params = null): string
    {
        $settings = Plugin::$plugin->getSettings();
        $baseBath = $settings->browserPath();
        return UrlHelper::siteUrl(trim($baseBath, '/') . '/' . trim($path, '/'), $params);
    }

    public static function friendlyNameFromHandle(string $handle): string
    {
        $path = self::resolveHandlePath($handle, 'twig');
        $info = pathinfo($path);
        $name = $info['filename'];
        $name = StringHelper::humanize($name);
        return $name;
    }

    /**
     * Resolve the handle path to a component file.
     * Example: `@components/button` -> `/path/to/components/button.twig`
     * Example: `@components/button--variant` -> `/path/to/components/button--variant.twig`
     * @param string $handle
     */
    public static function resolveHandlePath(string $handle, string $ext): string
    {
        $settings = Plugin::$plugin->getSettings();
        $relPath = self::resolveAliases($handle);
        $relPath = self::expandComponentPath($relPath, $ext);
        $relPath = str_replace($settings->root . '/', '', $relPath);
        $absPath = FileHelper::normalizePath($settings->root . '/' . $relPath);
        return $absPath;
    }

    /**
     * Add the default component file if the path ends with a directory name.
     * Example: `path/to/component` -> `path/to/component/component.twig`
     * Example: `path/to/component--variant` -> `path/to/component/component--variant.twig`
     * @param string $path
     * @return string
     */
    public static function expandComponentPath(string $path, string $ext = 'twig'): string
    {
        $rootPath = Plugin::$plugin->getSettings()->root;
        $canonicalPath = preg_replace('/--[^.]+/', '', $path);
        $normalizedPath = FileHelper::normalizePath($rootPath . '/' . $canonicalPath);
        if (is_dir($normalizedPath)) {
            $name = basename($path);
            $path = $canonicalPath . '/' . $name;
        }
        return self::ensureExtension($path, $ext);
    }

    public static function ensureExtension(string $filename, string $defaultExtension): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return empty($ext) ? $filename . '.' . $defaultExtension : $filename;
    }

    /**
     * Resolve aliases transforming (@text symbols) into corresponding paths from the settings.
     * @param string $path
     */
    public static function resolveAliases(string $path): string
    {
        $aliases = Plugin::$plugin->getSettings()->aliases;
        foreach ($aliases as $alias => $replacement) {
            $path = str_replace($alias, $replacement, $path);
        }
        // if the path is still an alias, remove the @ and treat it as a relative path
        if (strpos($path, '@') === 0) {
            $path = str_replace('@', '', $path);
        }
        return $path;
    }
}
