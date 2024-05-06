<?php

namespace madebyraygun\componentlibrary;

use Craft;
use craft\base\PluginTrait;
use yii\base\Event;
use craft\base\Plugin as BasePlugin;
use craft\base\Model;
use craft\web\View;
use craft\web\Application;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use madebyraygun\componentlibrary\web\twig\TemplateLoader;
use madebyraygun\componentlibrary\models\Settings;
use madebyraygun\componentlibrary\services\ComponentProvider;
use madebyraygun\componentlibrary\base\PluginLogTrait;

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
                'componentProvider' => ComponentProvider::class
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
                if ( !Craft::$app->request->isCpRequest ) {
                    $view = Craft::$app->getView();
                    $view->getTwig()->setLoader(new TemplateLoader($view));
                }
        });

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['component-library/preview'] = 'component-library/preview';
                $event->rules['component-library'] = 'component-library/browser';
            }
        );

        Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function(Event $event) {
            $event->roots['component-library'] = Craft::getAlias('@madebyraygun/component-library/templates');
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
