<?php

namespace madebyraygun\componentlibrary\helpers;

use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class Context
{
    private static array $cache = [];

    private static array $settingsDefaults = [
        'preview' => '@preview',
        'hidden' => false,
    ];

    public static function parseConfigParts(string $name): object
    {
        if (isset(Context::$cache[$name])) {
            return Context::$cache[$name];
        }
        $settings = self::getComponentSettings($name);
        $context = self::getComponentContext($name);
        $result = (object) [
            'settings' => $settings,
            'context' => $context,
        ];
        Context::$cache[$name] = $result;
        return $result;
    }

    public static function getVariants(string $name, bool $virtualOnly = false): array
    {
        $parts = Component::parseComponentParts($name);
        if ($parts->isVariant) {
            return [];
        }
        $config = self::getComponentConfig($name);
        $variants = $config['variants'] ?? [];
        $results = [];
        $info = pathinfo($name);
        foreach ($variants as $variant) {
            if (!empty($variant['name'])) {
                $variantName = Component::normalizeName($variant['name']);
                $fullVariantName = $info['dirname'] . '--' . $variantName;
                $variantParts = Component::parseComponentParts($fullVariantName);
                $shouldInsert = $virtualOnly == false || $variantParts->isVirtual;
                if ($shouldInsert) {
                    $results[] = $variantParts;
                }
            }
        }
        return $results;
    }

    public static function getComponentConfig(string $name): array
    {
        $parts = Component::parseComponentParts($name);
        $config = self::readConfigFile($name);
        $config['context'] = $config['context'] ?? [];
        $config['variants'] = $config['variants'] ?? [];
        $parentContext = $config['context'];
        if ($parts->isVariant && isset($config['variants'])) {
            $config = self::getVariantInConfig($config, $parts->name);
            // inherit parent context
            $config['context'] = array_merge($parentContext, $config['context'] ?? []);
            unset($config['variants']);
        }
        $config['name'] = $config['name'] ?? $parts->name;
        $config['title'] = empty($config['title']) ? $config['name'] : $config['title'];
        $config['title'] = StringHelper::humanize($config['title']);
        $config['name'] = StringHelper::dasherize($config['name']);
        return $config;
    }

    public static function getVariantInConfig(array $config, string $name): array|null
    {
        $variantNames = array_column($config['variants'], 'name');
        $variantNames = array_map([Component::class, 'normalizeName'], $variantNames);
        $idx = array_search($name, $variantNames);
        return $idx !== false ? $config['variants'][$idx] : null;
    }

    public static function getComponentContext(string $name): array
    {
        $config = self::getComponentConfig($name);
        $context = $config['context'];
        $result = self::resolveContextReferences($context);
        return $result;
    }

    public static function getComponentSettings(string $name): object
    {
        $config = self::getComponentConfig($name);
        unset($config['context']);
        unset($config['variants']);
        $settings = array_merge(self::$settingsDefaults, $config);
        return (object)$settings;
    }

    public static function readConfigFile(string $name): array
    {
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
            } elseif ($parts->configType === 'json') {
                $json = Json::decode(file_get_contents($parts->configPath));
                $config = is_array($json) ? $json : [];
            }
        }
        return $config;
    }

    public static function resolveContextReferences(array $context): array
    {
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
