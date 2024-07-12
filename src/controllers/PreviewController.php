<?php

namespace madebyraygun\componentlibrary\controllers;

use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\helpers\Loader;
use Craft;

class PreviewController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public function actionIndex(): Response
    {
        $name = $this->request->getParam('name');
        if (!$name) {
            Plugin::error('No component name provided');
            return $this->asFailure('No component name provided');
        }
        if (!Loader::componentExists($name)) {
            Plugin::error('Component not found');
            return $this->asFailure('Component not found');
        }
        $provider = Plugin::$plugin->componentProvider;
        $settings = $provider->getComponentSettings($name);
        $context = $provider->getComponentContext($name);
        $view = Craft::$app->getView();
        $html = $view->renderTemplate($name, $context);
        $previewContext = $provider->getComponentContext($settings->preview);
        return $this->renderTemplate($settings->preview, [
            ...$previewContext,
            'yield' => $html,
        ]);
    }
}
