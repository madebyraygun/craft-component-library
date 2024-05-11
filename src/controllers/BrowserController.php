<?php

namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\helpers\Library;

class BrowserController extends Controller
{
    public function actionIndex(): Response
    {
        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $library = Library::scanLibraryPath();
        return $this->renderTemplate('component-library/index', [
            'library' => $library,
            'distUrl' => $distUrl,
        ]);
    }

    public function actionWelcome(): Response
    {
        return $this->renderTemplate('component-library/welcome');
    }
}
