<?php

namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Response;
use madebyraygun\componentlibrary\helpers\Library;
use madebyraygun\componentlibrary\helpers\Loader;
class BrowserController extends BaseController
{
    protected array|int|bool $allowAnonymous = true;

    public function actionIndex(): Response
    {
        $name = Craft::$app->request->getParam('name');
        $iframeUrl = Library::getIsolatedPreviewUrl($name ?? '');
        $toolbarContext = Library::getUiToolbarContext($name ?? '');
        $componentsTree = Library::getLibraryTree();
        $documentsTree = Library::getDocumentsTree();
        $searchIndex = Library::getSearchIndexFromTrees([$componentsTree, $documentsTree]);
        return $this->renderPluginTemplate('index', [
            'component_name' => $name,
            'component_exists' => $name && Loader::handleExists($name),
            'sidebars' => [
                $componentsTree,
                $documentsTree,
            ],
            'search_bar' => [
                'index' => $searchIndex,
            ],
            'toolbar' => $toolbarContext,
            'iframeUrl' => $iframeUrl
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
}
