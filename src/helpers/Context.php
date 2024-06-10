<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\Json;
use craft\helpers\ArrayHelper;
use madebyraygun\componentlibrary\helpers\Component;

class Context {

    private static array $cache = [];

    private static array $settingsDefaults = [
        'preview' => '@preview',
        'hidden' => false,
    ];

    public static function parseConfigParts(string $name): object {
        if (isset(Context::$cache[$name])) {
            return Context::$cache[$name];
        }
        $settings = self::extractConfigSettings($name);
        $context = self::getComponentContext($name);
        $result = (object) [
            'settings' => $settings,
            'context' => $context,
        ];
        Context::$cache[$name] = $result;
        return $result;
    }

    public static function getComponentContext(string $name): array {
        $result = self::extractContext($name);
        $result = self::resolveContextReferences($result);
        return $result;
    }

    public static function extractConfigSettings(string $name): object {
        $parts = Component::parseComponentParts($name);
        $config = self::readConfigFile($name);
        $settings = $config ?? [];
        if (!empty($parts->isVariant) && isset($config['variants'])) {
            $idx = array_search($parts->variantName, array_column($config['variants'], 'name'));
            $entry = $config['variants'][$idx] ?? [];
            $settings = array_merge($settings, $entry);
        }
        unset($settings['context']);
        unset($settings['variants']);
        $settings = array_merge(self::$settingsDefaults, $settings);
        return (object)$settings;
    }

    public static function extractContext(string $name): array {
        $parts = Component::parseComponentParts($name);
        $config = self::readConfigFile($name);
        $context = $config['context'] ?? [];
        if (!empty($parts->isVariant) && isset($config['variants'])) {
            $idx = array_search($parts->variantName, array_column($config['variants'], 'name'));
            $entry = $config['variants'][$idx] ?? [];
            $variantContext = $entry['context'] ?? [];
            $context = array_merge($context, $variantContext);
        }
        return $context;
    }

    public static function readConfigFile(string $name): array {
        $parts = Component::parseComponentParts($name);
        $config = [];
        if (file_exists($parts->configPath)) {
            if ($parts->configType === 'php') {
                try {
                    $config = require $parts->configPath;
                    $config = is_array($config) ? $config : [];
                } catch (\Throwable $e) {
                    $config = [];
                }
            } else if ($parts->configType === 'json') {
                $json = Json::decode(file_get_contents($parts->configPath));
                $config = is_array($json) ? $json : [];
            }
        }
        return $config;
    }

    public static function resolveContextReferences(array $context): array {
        $resolved = [];
        foreach ($context as $key => $value) {
            if (is_string($value) && strpos($value, '@') === 0) {
                $parts = explode('.', $value);
                $componentInclude = $parts[0] ?? '';
                $contextPath = $parts[1] ?? '';
                $refContext = self::getComponentContext($componentInclude);
                if (empty($refContext)) {
                    // If the reference context is empty, assign the reference value
                    // so the user can see what's going on
                    $resolved[$key] = $value;
                    continue;
                }
                if (empty($contextPath)) {
                    // If no context path is provided, assign the entire context
                    $resolved[$key] = $refContext;
                } else {
                    // Otherwise, assign the value at the context path
                    $resolved[$key] = ArrayHelper::getValue($refContext, $contextPath);
                }
            } else {
                $resolved[$key] = $value;
            }
        }
        return $resolved;
    }
}
