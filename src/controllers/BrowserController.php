<?php

namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\helpers\Library;
use madebyraygun\componentlibrary\helpers\Loader;

class BrowserController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public function actionIndex(): Response
    {
        // read name parameter from the request
        $name = Craft::$app->request->getParam('name');
        if (!empty($name) && !Loader::handleExists($name)) {
            return $this->asFailure('Component not found');
        }

        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $iframeUrl = Library::getIsolatedPreviewUrl($name ?? '');
        $libraryUrl = UrlHelper::siteUrl('/component-library');
        $toolbarContext = Library::getUiToolbarContext($name);
        $componentsSidebar = Library::scanLibraryPath();
        $documentsSidebar = Library::scanDocumentsPath();
        return $this->renderTemplate('component-library/index', [
            'sidebars' => [
                $componentsSidebar,
                $documentsSidebar,
            ],
            'toolbar' => $toolbarContext,
            'iframeUrl' => $iframeUrl,
            'libraryUrl' => $libraryUrl,
            'distUrl' => $distUrl,
        ]);
    }

    public function actionPartialToolbar(): Response
    {
        $name = Craft::$app->request->getParam('name');
        return $this->renderTemplate('component-library/_partials/toolbar', [
            'toolbar' => Library::getUiToolbarContext($name ?? ''),
        ]);
    }

    public function actionPartialPreview(): Response
    {
        $name = Craft::$app->request->getParam('name');
        $iframeUrl = Library::getIsolatedPreviewUrl($name ?? '');
        return $this->renderTemplate('component-library/_partials/preview', [
            'iframeUrl' => $iframeUrl,
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
