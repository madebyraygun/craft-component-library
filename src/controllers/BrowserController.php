<?php

namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use craft\helpers\UrlHelper;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\helpers\Library;
use madebyraygun\componentlibrary\helpers\Component;
use madebyraygun\componentlibrary\helpers\Context;

class BrowserController extends Controller
{
    public function actionIndex(): Response
    {
        // read name parameter from the request
        $name = Craft::$app->request->getParam('name');
        $context = null;
        if (!empty($name)) {
            $context = [
                'name' => $name,
                'exists' => Component::componentExists($name),
                'component' => Component::parseComponentParts($name),
                'context' => Context::parseConfigParts($name),
            ];
        }

        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $iframeUrl = Library::getIsolatedPreviewUrl($name);
        $library = Library::scanLibraryPath();
        return $this->renderTemplate('component-library/index', [
            'library' => $library,
            'context' => $context,
            'iframeUrl' => $iframeUrl,
            'distUrl' => $distUrl,
        ]);
    }

    public function actionNotFound(): Response
    {
        return $this->renderTemplate('component-library/not-found');
    }

    public function actionWelcome(): Response
    {
        return $this->renderTemplate('component-library/welcome');
    }
}
