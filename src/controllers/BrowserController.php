<?php

namespace madebyraygun\componentlibrary\controllers;

use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\helpers\Library;

class BrowserController extends Controller
{
    public function actionIndex(): Response
    {
        $this->view->registerAssetBundle(LibraryBrowserAssets::class);
        $library = Library::scanLibraryPath();
        return $this->renderTemplate('component-library/browser', [
            'library' => $library
        ]);
    }
}
