<?php

namespace madebyraygun\componentlibrary\controllers;

use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\Plugin;
use Craft;

class PreviewController extends Controller
{
    public function actionIndex(): Response
    {
        $name = $this->request->getParam('name');
        if (!$name) {
            Plugin::error('No component name provided');
            return $this->asErrorJson('No component name provided');
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