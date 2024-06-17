<?php

namespace madebyraygun\componentlibrary\services;

use craft\base\Component;
use madebyraygun\componentlibrary\helpers\Component as ComponentHelper;
use madebyraygun\componentlibrary\helpers\Context as ContextHelper;
use madebyraygun\componentlibrary\helpers\Loader as LoaderHelper;

class ComponentProvider extends Component
{
    /**
     * Resolve the path to a component file.
     * Example: `@components/button` -> `/path/to/components/button.twig`
     * Example: `@components/button--variant` -> `/path/to/components/button--variant.twig`
     * @param string $path
     */
    public function resolveComponentPath(string $path): string|null
    {
        if (!LoaderHelper::componentExists($path)) {
            return null;
        }
        $parts = ComponentHelper::parseComponentParts($path);
        return $parts->templatePath;
    }

    /**
     * Get the context for a component.
     * Example: `@components/button` -> `['title' => 'Button']`
     * @param string $path The path notation for the component
     * @return array
     */
    public function getComponentContext(string $path): array|null
    {
        if (!LoaderHelper::componentExists($path)) {
            return null;
        }
        $parts = ContextHelper::parseConfigParts($path);
        return $parts->context;
    }

    /**
     * Get the settings for a component by reading the config file.
     * Example: `@components/button` -> `['preview => '@preview', 'hidden' => false]`
     * @param string $path The path notation for the component
     * @return array
     */
    public function getComponentSettings(string $path): object|null
    {
        if (!LoaderHelper::componentExists($path)) {
            return null;
        }
        $parts = ContextHelper::parseConfigParts($path);
        return $parts->settings;
    }
}
