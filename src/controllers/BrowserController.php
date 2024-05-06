<?php

namespace madebyraygun\componentlibrary\controllers;

use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\helpers\Library;
use Craft;

class BrowserController extends Controller
{
    public function actionIndex(): Response
    {
        $library = Library::scanLibraryPath();
        return $this->renderTemplate('component-library/browser', [
            'library' => $library
        ]);
    }
}
