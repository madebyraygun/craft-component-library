<?php

namespace madebyraygun\componentlibrary\helpers;

class Component
{
    private static array $cache = [];

    public static function parseComponentParts(string $name): object
    {
        if (!Common::isUsableHandle($name)) {
            return self::unknownParts($name);
        }
        $name = Common::collapseHandlePath($name);
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        if (!Common::isHandleInsideLibrary($name)) {
            return self::$cache[$name] = self::unknownParts($name);
        }
        $componentPath = Common::resolveHandlePath($name, 'twig');
        $canonicalPath = preg_replace('/--[^.]+/', '', $componentPath);
        if (!Common::isInsideLibrary($componentPath) || !Common::isInsideLibrary($canonicalPath)) {
            return self::$cache[$name] = self::unknownParts($name);
        }
        $isVirtual = !file_exists($componentPath);
        $variantName = self::getVariantName($componentPath);
        $defaultName = self::getCanonicalName($componentPath);
        $canonicalInfo = pathinfo($canonicalPath);
        $templateInfo = $isVirtual ? $canonicalInfo : pathinfo($componentPath);
        $isVariant = $componentPath !== $canonicalPath;
        $templatePath = $isVirtual ? $canonicalPath : $componentPath;
        if (!str_ends_with($templatePath, '.twig') || !str_ends_with($canonicalPath, '.twig')) {
            return self::$cache[$name] = self::unknownParts($name);
        }
        if (file_exists($templatePath) && !Common::isFileInsideLibrary($templatePath)) {
            return self::$cache[$name] = self::unknownParts($name);
        }
        $result = (object)[
            'type' => 'component',
            'icon' => 'deployed_code',
            'valid' => true,
            'name' => $isVariant ? $variantName : $defaultName,
            'includeName' => $name,
            'templateName' => $templateInfo['basename'],
            'templateDir' => $templateInfo['dirname'],
            'templatePath' => $templatePath,
            'canonicalName' => $canonicalInfo['basename'],
            'canonicalPath' => $canonicalPath,
            'isVariant' => $isVariant,
            'isVirtual' => $isVirtual,
            ...self::getDocParts($name),
            ...self::getConfigParts($canonicalPath),
        ];
        Component::$cache[$name] = $result;
        return $result;
    }

    /**
     * The parts of a handle that is not a component template in the library.
     */
    private static function unknownParts(string $name): object
    {
        return (object)[
            'type' => 'component',
            'icon' => 'deployed_code',
            'valid' => false,
            'name' => '',
            'includeName' => $name,
            'templateName' => '',
            'templateDir' => '',
            'templatePath' => '',
            'canonicalName' => '',
            'canonicalPath' => '',
            'isVariant' => false,
            'isVirtual' => false,
            'docPath' => '',
            'docExists' => false,
            'configType' => '',
            'configPath' => '',
            'configExists' => false,
        ];
    }

    public static function getConfigParts(string $canonicalPath): array
    {
        if (str_ends_with($canonicalPath, '.twig')) {
            $basePath = substr($canonicalPath, 0, -strlen('.twig'));
            foreach (['php', 'json'] as $configType) {
                $configPath = $basePath . '.config.' . $configType;
                if (Common::isFileInsideLibrary($configPath)) {
                    return [
                        'configType' => $configType,
                        'configPath' => $configPath,
                        'configExists' => true,
                    ];
                }
            }
        }
        return [
            'configType' => '',
            'configPath' => '',
            'configExists' => false,
        ];
    }

    public static function getDocParts(string $templatePath): array
    {
        $docParts = Document::getDocPartsFromTemplate($templatePath);
        return [
            'docPath' => $docParts->docPath,
            'docExists' => $docParts->valid,
        ];
    }

    public static function getVariantName(string $name): string
    {
        $nameParts = explode('--', $name);
        $result = count($nameParts) > 1 ? array_pop($nameParts) : '';
        return preg_replace('/\..*/', '', $result);
    }

    public static function getCanonicalName(string $name): string
    {
        $nameParts = explode('--', $name);
        $result = array_shift($nameParts);
        return pathinfo($result)['filename'];
    }
}
