<?php

namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Response;
use madebyraygun\componentlibrary\helpers\Library;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\helpers\Common;
class BrowserController extends BaseController
{
    protected array|int|bool $allowAnonymous = true;

    public function actionIndex(): Response
    {
        $name = Craft::$app->request->getParam('name');
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $iframeUrl = Library::getIsolatedPreviewUrl($name ?? '');
        $libraryUrl = Common::libraryUrl('/');
        $toolbarContext = Library::getUiToolbarContext($name ?? '');
        $componentsTree = Library::getLibraryTree();
        $documentsTree = Library::getDocumentsTree();
        $searchIndex = Library::getSearchIndexFromTrees([$componentsTree, $documentsTree]);
        return $this->renderPluginTemplate('index', [
            'sidebars' => [
                $componentsTree,
                $documentsTree,
            ],
            'search_bar' => [
                'index' => $searchIndex,
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
        return $this->renderPluginTemplate('_partials/toolbar', [
            'toolbar' => Library::getUiToolbarContext($name ?? ''),
        ]);
    }

    public function actionPartialPreview(): Response
    {
        $name = Craft::$app->request->getParam('name');
        $iframeUrl = Library::getIsolatedPreviewUrl($name ?? '');
        return $this->renderPluginTemplate('_partials/preview', [
            'iframeUrl' => $iframeUrl,
        ]);
    }

    public function actionIcon(): Response
    {
        $iconPath = Craft::getAlias('@madebyraygun/componentlibrary/icon.svg');
        $icon = file_get_contents($iconPath);
        Craft::$app->response->headers->set('Content-Type', 'image/svg+xml');
        return $this->asRaw($icon);
    }

    public function actionNotFound(): Response
    {
        return $this->renderPluginTemplate('not-found');
    }

    public function actionWelcome(): Response
    {
        return $this->renderPluginTemplate('welcome');
    }
}
