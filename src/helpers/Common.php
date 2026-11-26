<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use madebyraygun\componentlibrary\Plugin;

class Common
{
    public const MAX_HANDLE_LENGTH = 512;

    /**
     * Whether a handle can be turned into a path at all.
     */
    public static function isUsableHandle(string $handle): bool
    {
        return $handle !== '' && strlen($handle) <= self::MAX_HANDLE_LENGTH && !str_contains($handle, "\0");
    }

    /**
     * A handle that always resolves through the library, never through Craft's own template paths.
     */
    public static function libraryHandle(string $handle): string
    {
        return str_starts_with($handle, '@') ? $handle : '@' . $handle;
    }

    /**
     * Whether a handle points inside the library root or the docs folder.
     * Works on the path as text and touches no files, so it says nothing about what exists outside.
     */
    public static function isHandleInsideLibrary(string $handle): bool
    {
        if (!self::isUsableHandle($handle)) {
            return false;
        }
        $root = Plugin::$plugin->getSettings()->root;
        $relPath = str_replace($root . '/', '', self::resolveAliases($handle));
        return self::isInsideLibrary($root . '/' . $relPath);
    }

    /**
     * Whether a path, once `.` and `..` are resolved, is inside the library root or the docs folder.
     * Works on the path as text and touches no files.
     */
    public static function isInsideLibrary(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }
        return self::startsWithARoot(FileHelper::normalizePath($path), false);
    }

    /**
     * Whether a path is an existing file that is still inside the library root or the docs folder
     * after symlinks are resolved.
     */
    public static function isFileInsideLibrary(string $path): bool
    {
        if (!self::isInsideLibrary($path)) {
            return false;
        }
        $realPath = realpath($path);
        return $realPath !== false && is_file($realPath) && self::startsWithARoot($realPath, true);
    }

    /**
     * A path as shown in the browser: relative to the library root or the docs folder.
     */
    public static function relativePath(string $path): string
    {
        $path = FileHelper::normalizePath($path);
        foreach (self::roots(false) as $root) {
            if (str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
                return substr($path, strlen($root) + 1);
            }
        }
        return basename($path);
    }

    private static function startsWithARoot(string $path, bool $real): bool
    {
        foreach (self::roots($real) as $root) {
            if (str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[] the library root and the docs folder, as configured or with symlinks resolved
     */
    private static function roots(bool $real): array
    {
        $settings = Plugin::$plugin->getSettings();
        $roots = [];
        foreach ([$settings->root, $settings->docs] as $root) {
            if ($root === '') {
                continue;
            }
            $root = $real ? realpath($root) : FileHelper::normalizePath($root);
            if ($root !== false && $root !== '.' && $root !== DIRECTORY_SEPARATOR) {
                $roots[] = $root;
            }
        }
        return $roots;
    }

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
     * Collapse a handle path into it's shortest form.
     * This might be seen as the opposite of what expandComponentPath does.
     * Additionally it will try to collapse the path into defined aliases.
     * Example: `@components/button/button.twig` -> `@components/button`
     * Example: `@components/button/button--variant.twig` -> `@components/button--variant`
     * @param string $handle
     * @return string
     */
    public static function collapseHandlePath(string $handle): string
    {
        $handle = strtolower($handle);
        // Collapse path into defined aliases
        $alias = Plugin::$plugin->getSettings()->aliases;
        foreach ($alias as $key => $value) {
            if (strpos($handle, $value) === 0) {
                $handle = str_replace($value, $key, $handle);
                break;
            }
        }

        // Remove the file extension
        $path = preg_replace('/\.[^.]+$/', '', $handle);
        $segments = explode('/', $path);
        if (count($segments) <= 1) {
            return $handle;
        }
        // Find the parent directory and the component name
        $parentDir = $segments[count($segments) - 2];
        $componentName = $segments[count($segments) - 1];
        $isVariant = strpos($componentName, '--');
        if ($isVariant) {
            $componentName = explode('--', $componentName)[0];
        }
        if ($componentName === $parentDir) {
            // Collapse component names
            array_splice($segments, -2, 1);
        }
        return implode('/', $segments);
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
