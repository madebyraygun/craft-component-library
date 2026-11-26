<?php

namespace madebyraygun\componentlibrary\helpers;

class Component
{
    private static array $cache = [];

    public static function parseComponentParts(string $name): object
    {
        if (!Common::isUsableHandle($name)) {
            return self::outsideParts($name);
        }
        $name = Common::collapseHandlePath($name);
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        if (!Common::isHandleInsideLibrary($name)) {
            return self::$cache[$name] = self::outsideParts($name);
        }
        $componentPath = Common::resolveHandlePath($name, 'twig');
        $canonicalPath = preg_replace('/--[^.]+/', '', $componentPath);
        if (!Common::isInsideLibrary($componentPath) || !Common::isInsideLibrary($canonicalPath)) {
            return self::$cache[$name] = self::outsideParts($name);
        }
        $isVirtual = !file_exists($componentPath);
        $variantName = self::getVariantName($componentPath);
        $defaultName = self::getCanonicalName($componentPath);
        $canonicalInfo = pathinfo($canonicalPath);
        $templateInfo = $isVirtual ? $canonicalInfo : pathinfo($componentPath);
        $isVariant = $componentPath !== $canonicalPath;
        $templatePath = $isVirtual ? $canonicalPath : $componentPath;
        if (file_exists($templatePath) && !Common::isFileInsideLibrary($templatePath)) {
            return self::$cache[$name] = self::outsideParts($name);
        }
        $result = (object)[
            'type' => 'component',
            'icon' => 'deployed_code',
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

    /**
     * The parts of a handle that does not resolve to a file inside the library.
     * It carries no paths, so nothing that receives it can read or include a file.
     */
    private static function outsideParts(string $name): object
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
        $jsonConfigPath = str_replace('.twig', '.config.json', $canonicalPath);
        $phpConfigPath = str_replace('.twig', '.config.php', $canonicalPath);
        $configType = '';
        $configPath = '';
        if (Common::isFileInsideLibrary($phpConfigPath)) {
            $configType = 'php';
            $configPath = $phpConfigPath;
        } elseif (Common::isFileInsideLibrary($jsonConfigPath)) {
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

    public static function getCanonicalName(string $name): string
    {
        $nameParts = explode('--', $name);
        $result = array_shift($nameParts);
        return pathinfo($result)['filename'];
    }
}
