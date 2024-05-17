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
        $toobarContext = Library::getUiToolbarContext($name ?? '');
        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $iframeUrl = Library::getIsolatedPreviewUrl($name ?? '');
        $libraryUrl = UrlHelper::siteUrl('/component-library');
        $library = Library::scanLibraryPath();
        return $this->renderTemplate('component-library/index', [
            'library' => $library,
            'toolbar' => $toobarContext,
            'iframeUrl' => $iframeUrl,
            'libraryUrl' => $libraryUrl,
            'distUrl' => $distUrl,
        ]);
    }

    public function actionToolbar(): Response
    {
        $name = Craft::$app->request->getParam('name');
        return $this->asJson(Library::getUiToolbarContext($name ?? ''));
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
