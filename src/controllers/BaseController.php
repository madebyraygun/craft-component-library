<?php
namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Response;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\helpers\Loader;
use madebyraygun\componentlibrary\helpers\Common;
use yii\base\InvalidRouteException;

class BaseController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public const PLUGIN_TEMPLATE_PATH = '@madebyraygun/component-library/templates';

    public function beforeAction($action): bool
    {
        $this->requireBrowserEnabled();
        if (Plugin::getInstance()->getSettings()->browserRequiresLogin() && Craft::$app->getUser()->getIsGuest()) {
            $this->sendGuestToLogin();
        }
        return parent::beforeAction($action);
    }

    /**
     * @throws InvalidRouteException if the library browser is disabled. Craft answers it exactly as
     * it answers a route that does not exist.
     */
    protected function requireBrowserEnabled(): void
    {
        if (!Plugin::getInstance()->getSettings()->browserEnabled()) {
            throw new InvalidRouteException('Unable to resolve the request "' . Craft::$app->requestedRoute . '".');
        }
    }

    protected function sendGuestToLogin(): void
    {
        $settings = Plugin::getInstance()->getSettings();
        Craft::$app->user->returnUrl = UrlHelper::cpUrl($settings->browserPath());
        Craft::$app->user->loginUrl = UrlHelper::cpUrl('login');
        $this->requireLogin();
    }

    public function getCurrentSelection(): array
    {
        $handle = Craft::$app->request->getParam('name') ?? '';
        $exists = Loader::handleExists($handle);
        $parts = Loader::parseHandleParts($handle);
        $result = [
            'empty' => empty($handle),
            'exists' => $exists,
            'handle' => $parts->includeName,
            'type' => $parts->type,
            'name' => $parts->name,
            'icon' => $parts->icon,
        ];
        return $result;
    }

    public function renderPluginTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
    {
        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $libraryUrl = Common::libraryUrl('/');
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $variables = array_merge($variables, [
            'config' => Plugin::getInstance()->getSettings(),
            'current' => $this->getCurrentSelection(),
            'libraryUrl' => $libraryUrl,
            'distUrl' => $distUrl,
        ]);
        $this->view->setTemplatesPath(Craft::getAlias(self::PLUGIN_TEMPLATE_PATH));
        return $this->renderTemplate($template, $variables, $templateMode);
    }

    public function renderPluginView(string $name, array $context = []): string
    {
        $view = Craft::$app->getView();
        $before = $view->templatesPath;
        $view->setTemplatesPath(Craft::getAlias(self::PLUGIN_TEMPLATE_PATH));
        $html = $view->renderTemplate($name, $context);
        $view->setTemplatesPath($before);
        return $html;
    }
}
