<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use madebyraygun\componentlibrary\Plugin;

class Component
{
    private static array $cache = [];

    public static function parseComponentParts(string $name): object {
        $name = strtolower($name);
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        $componentPath = self::resolveComponentPath($name);
        $isVirtual = !file_exists($componentPath);
        $canonicalPath = preg_replace('/--[^.]+/', '', $componentPath);
        $variantName = self::getVariantName($componentPath);
        $defaultName = self::getDefaultName($componentPath);
        $canonicalInfo = pathinfo($canonicalPath);
        $templateInfo = $isVirtual ? $canonicalInfo : pathinfo($componentPath);
        $isVariant = $componentPath !== $canonicalPath;
        $result = (object)[
            'name' => $isVariant ? $variantName : $defaultName,
            'includeName' => $name,
            'templateName' => $templateInfo['basename'],
            'templateDir' => $templateInfo['dirname'],
            'templatePath' => $isVirtual ? $canonicalPath : $componentPath,
            'canonicalName' => $canonicalInfo['basename'],
            'canonicalPath' => $canonicalPath,
            'isVariant' => $isVariant,
            'isVirtual' => $isVirtual,
            ...self::getConfigParts($canonicalPath)
        ];
        Component::$cache[$name] = $result;
        return $result;
    }

    public static function getConfigParts(string $canonicalPath): array {
        $jsonConfigPath = str_replace('.twig', '.config.json', $canonicalPath);
        $phpConfigPath = str_replace('.twig', '.config.php', $canonicalPath);
        $configType = '';
        $configPath = '';
        if (file_exists($phpConfigPath)) {
            $configType = 'php';
            $configPath = $phpConfigPath;
        } else if (file_exists($jsonConfigPath)) {
            $configType = 'json';
            $configPath = $jsonConfigPath;
        }
        return [
            'configType'=> $configType,
            'configPath' => $configPath,
            'configExists' => !empty($configPath)
        ];
    }

    public static function normalizeName(string $name): string {
        $name = strtolower($name);
        $name = StringHelper::dasherize($name);
        return $name;
    }

    public static function getVariantName(string $name): string {
        $nameParts = explode('--', $name);
        $result = count($nameParts) > 1 ? array_pop($nameParts) : '';
        return preg_replace('/\..*/', '', $result);
    }

    public static function getDefaultName(string $name): string {
        $nameParts = explode('--', $name);
        $result = array_shift($nameParts);
        return pathinfo($result)['filename'];
    }

    /**
     * Resolve the path to a component file.
     * Example: `@components/button` -> `/path/to/components/button.twig`
     * Example: `@components/button--variant` -> `/path/to/components/button--variant.twig`
     * @param string $name
     */
    public static function resolveComponentPath(string $name): string {
        $settings = Plugin::$plugin->getSettings();
        $relPath = self::resolveAliases($name);
        $relPath = self::getDefaultComponentFile($relPath);
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
    public static function getDefaultComponentFile(string $path): string {
        $rootPath = Plugin::$plugin->getSettings()->root;
        $canonicalPath = preg_replace('/--[^.]+/', '', $path);
        $normalizedPath = FileHelper::normalizePath($rootPath . '/'. $canonicalPath);
        if (is_dir($normalizedPath)) {
            $name = basename($path);
            $path = $canonicalPath . '/' . $name;
        }
        return self::ensureExtension($path, 'twig');
    }

    public static function ensureExtension(string $filename, string $defaultExtension): string {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return empty($ext) ? $filename . '.' . $defaultExtension : $filename;
    }

    /**
     * Resolve aliases transforming (@text symbols) into corresponding paths from the settings.
     * @param string $path
     */
    public static function resolveAliases(string $path): string {
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
