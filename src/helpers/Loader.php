<?php

namespace madebyraygun\componentlibrary\helpers;

use madebyraygun\componentlibrary\helpers\Component;
use madebyraygun\componentlibrary\helpers\Context;

class Loader {
    public static function componentExists(string $name): bool
    {
        $parts = Component::parseComponentParts($name);
        if ($parts->isVirtual)
        {
            $parentName = strstr($name, '--', true);
            $config = Context::readConfigFile($parentName);
            $variant = Context::getVariantInConfig($config, $parts->name);
            if (empty($variant))
            {
                return false;
            }
        }
        return file_exists($parts->templatePath);
    }
}
