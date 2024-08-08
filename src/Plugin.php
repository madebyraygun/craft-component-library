<?php

namespace madebyraygun\componentlibrary;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\Application;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use madebyraygun\componentlibrary\base\PluginLogTrait;
use madebyraygun\componentlibrary\models\Settings;
use madebyraygun\componentlibrary\services\ComponentProvider;
use madebyraygun\componentlibrary\variables\ViteVariables;
use madebyraygun\componentlibrary\web\twig\TemplateLoader;
use nystudio107\pluginvite\services\VitePluginService;
use yii\base\Event;

/**
 * component-library plugin
 *
 * @method static Plugin getInstance()
 */
class Plugin extends BasePlugin
{
    public static $plugin;

    public static $pluginHandle = 'component-library';

    public string $schemaVersion = '1.0.0';

    use PluginLogTrait;

    public static function config(): array
    {
        return [
            'components' => [
                'componentProvider' => ComponentProvider::class,
                'vite' => [
                    'class' => VitePluginService::class,
                    'checkDevServer' => true,
                    'useDevServer' => true,
                    'manifestPath' => Craft::getAlias('@webroot') . '/dist/.vite/manifest.json',
                    'devServerPublic' => Craft::getAlias('@web') . ':8080',
                    'devServerInternal' => 'http://localhost:8080',
                    'serverPublic' => Craft::getAlias('@web') . '/dist/',
                    // 'includeReactRefreshShim' => false,
                    // 'errorEntry' => 'js/main.js',
                    'cacheKeySuffix' => '',

                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        self::$plugin = $this;
        $this->installEventListeners();
        $this->registerLogger();
        Craft::setAlias('@madebyraygun/component-library', $this->getBasePath());
        $this->controllerNamespace = 'madebyraygun\componentlibrary\controllers';
        self::log('Component Library plugin loaded');
    }

    protected function installEventListeners(): void
    {
        Event::on(
            Application::class,
            Application::EVENT_INIT,
            function(Event $event) {
                if (!Craft::$app->request->isCpRequest) {
                    $view = Craft::$app->getView();
                    $view->getTwig()->setLoader(new TemplateLoader($view));
                }
            });

        if ($this->getSettings()->browserEnabled()) {
            $path = $this->getSettings()->browserPath();
            Event::on(
                UrlManager::class,
                UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                function(RegisterUrlRulesEvent $event) use ($path) {
                    $event->rules[$path . '/preview'] = 'component-library/preview';
                    $event->rules[$path . '/partials/toolbar'] = 'component-library/browser/partial-toolbar';
                    $event->rules[$path . '/partials/preview'] = 'component-library/browser/partial-preview';
                    $event->rules[$path . '/welcome'] = 'component-library/browser/welcome';
                    $event->rules[$path . '/not-found'] = 'component-library/browser/not-found';
                    $event->rules[$path . '/icon.svg'] = 'component-library/browser/icon';
                    $event->rules[$path] = 'component-library/browser';
                }
            );
        }

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                $variable = $event->sender;
                $variable->set('library', [
                    'class' => ViteVariables::class,
                    'viteService' => $this->vite,
                ]);
            }
        );
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
