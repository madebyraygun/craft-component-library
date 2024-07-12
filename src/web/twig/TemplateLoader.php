<?php
/**
 * Template loader plugin for Craft CMS 5.x
 *
 * @link      https://madebyraygun.com/
 * @copyright Copyright (c) 2022 Raygun Design, LLC
 */

namespace madebyraygun\componentlibrary\web\twig;

use Craft;
use craft\web\View;
use craft\web\twig\TemplateLoaderException;
use Twig\Loader\LoaderInterface;
use Twig\Source;
use madebyraygun\componentlibrary\Plugin;

/**
 * Loads Craft templates into Twig.
 *
 * @author Raygun Design, LLC. <dev@madebyraygun.com>
 * @since 4.0.0
 */
class TemplateLoader implements LoaderInterface
{
    /**
     * @var View|null
     */
    protected ?View $view = null;

    /**
     * Constructor
     *
     * @param View $view
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * @inheritdoc
     */
    public function exists(string $name): bool
    {
        return $this->view->doesTemplateExist($name);
    }

    /**
     * @inheritdoc
     */
    public function getSourceContext(string $name): Source
    {
        $template = $this->_resolveTemplate($name);

        if (!is_readable($template)) {
            throw new TemplateLoaderException($name, Craft::t('app', 'Tried to read the template at {path}, but could not. Check the permissions.', ['path' => $template]));
        }

        return new Source(file_get_contents($template), $name, $template);
    }

    /**
     * Gets the cache key to use for the cache for a given template.
     *
     * @param string $name The name of the template to load
     * @return string The cache key (the path to the template)
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function getCacheKey(string $name): string
    {
        return $this->_resolveTemplate($name);
    }

    /**
     * Returns whether the cached template is still up to date with the latest template.
     *
     * @param string $name The template name
     * @param int $time The last modification time of the cached template
     * @return bool
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    public function isFresh(string $name, int $time): bool
    {
        // If this is a control panel request and a DB update is needed, force a recompile.
        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest() && Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
            return false;
        }

        $sourceModifiedTime = filemtime($this->_resolveTemplate($name));
        return $sourceModifiedTime <= $time;
    }

    /**
     * Returns the path to a given template, or throws a TemplateLoaderException.
     *
     * @param string $name
     * @return string
     * @throws TemplateLoaderException if the template doesn’t exist
     */
    private function _resolveTemplate(string $name): string
    {
        if (strpos($name, '@') === 0)
        {
            $template = Plugin::$plugin->componentProvider->resolveFilePath($name);
            if (!$template || !is_readable($template)) {
                throw new TemplateLoaderException($name, Craft::t('app', 'Unable to resolve template "{name}" at {template}.', [
                    'path' => $template,
                    'name' => $name,
                ]));
            }
        } else {
            $template = $this->view->resolveTemplate($name);
        }

        if ($template !== false) {
            return $template;
        }

        throw new TemplateLoaderException($name, Craft::t('app', 'Unable to find the template “{template}”.', ['template' => $name]));
    }
}
