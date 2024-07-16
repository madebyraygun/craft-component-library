<?php

namespace madebyraygun\componentlibrary\helpers;

class Component
{
    private static array $cache = [];

    public static function parseComponentParts(string $name): object
    {
        $name = strtolower($name);
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        $componentPath = Common::resolveHandlePath($name, 'twig');
        $isVirtual = !file_exists($componentPath);
        $canonicalPath = preg_replace('/--[^.]+/', '', $componentPath);
        $variantName = self::getVariantName($componentPath);
        $defaultName = self::getDefaultName($componentPath);
        $canonicalInfo = pathinfo($canonicalPath);
        $templateInfo = $isVirtual ? $canonicalInfo : pathinfo($componentPath);
        $isVariant = $componentPath !== $canonicalPath;
        $templatePath = $isVirtual ? $canonicalPath : $componentPath;
        $result = (object)[
            'valid' => $templateInfo['extension'] === 'twig',
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

    public static function getConfigParts(string $canonicalPath): array
    {
        $jsonConfigPath = str_replace('.twig', '.config.json', $canonicalPath);
        $phpConfigPath = str_replace('.twig', '.config.php', $canonicalPath);
        $configType = '';
        $configPath = '';
        if (file_exists($phpConfigPath)) {
            $configType = 'php';
            $configPath = $phpConfigPath;
        } elseif (file_exists($jsonConfigPath)) {
            $configType = 'json';
            $configPath = $jsonConfigPath;
        }
        return [
            'configType' => $configType,
            'configPath' => $configPath,
            'configExists' => !empty($configPath),
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

    public static function getDefaultName(string $name): string
    {
        $nameParts = explode('--', $name);
        $result = array_shift($nameParts);
        return pathinfo($result)['filename'];
    }
}
