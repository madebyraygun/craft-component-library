<?php
namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Response;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\Plugin;

class BaseController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    private string $pluginTemplatePath = '@madebyraygun/component-library/templates';

    public function beforeAction($action): bool
    {
        $userSession = Craft::$app->getUser();
        $settings = Plugin::getInstance()->getSettings();
        if ($settings->browserRequiresLogin() && $userSession->getIsGuest()) {
            Craft::$app->user->returnUrl = UrlHelper::cpUrl($settings->browserPath());
            Craft::$app->user->loginUrl = UrlHelper::cpUrl('login');
            $this->requireLogin();
        }
        return parent::beforeAction($action);
    }

    public function renderPluginTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
    {
        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $this->view->setTemplatesPath(Craft::getAlias($this->pluginTemplatePath));
        return $this->renderTemplate($template, $variables, $templateMode);
    }

    public function renderPluginView(string $name, array $context = []): string
    {
        $view = Craft::$app->getView();
        $before = $view->templatesPath;
        $view->setTemplatesPath(Craft::getAlias($this->pluginTemplatePath));
        $html = $view->renderTemplate($name, $context);
        $view->setTemplatesPath($before);
        return $html;
    }
}
