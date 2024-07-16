<?php

namespace madebyraygun\componentlibrary\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use madebyraygun\componentlibrary\assetbundles\LibraryBrowserAssets;
use madebyraygun\componentlibrary\helpers\Loader;
use madebyraygun\componentlibrary\Plugin;

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

        if (Loader::componentExists($name)) {
            return $this->renderComponentTemplate($name);
        } elseif (Loader::documentExists($name)) {
            return $this->renderDocumentTemplate($name);
        } else {
            Plugin::error('Component or document not found');
            return $this->asFailure('Component or document not found');
        }
    }

    private function renderDocumentTemplate(string $name): Response
    {
        $provider = Plugin::$plugin->componentProvider;
        $context = $provider->getDocumentContext($name) ?? [];
        $view = Craft::$app->getView();
        $view->registerAssetBundle(LibraryBrowserAssets::class);
        $distUrl = Craft::$app->assetManager->getPublishedUrl('@madebyraygun/componentlibrary/assetbundles/dist', true);
        $html = $view->renderTemplate('component-library/_partials/document', [
            ...$context,
            'distUrl' => $distUrl,
        ]);
        return $this->renderTemplate('component-library/_previews/default', [
            'yield' => $html,
        ]);
    }

    private function renderComponentTemplate(string $name): Response
    {
        $provider = Plugin::$plugin->componentProvider;
        $settings = $provider->getComponentSettings($name);
        $context = $provider->getComponentContext($name) ?? [];
        $view = Craft::$app->getView();
        $html = $view->renderTemplate($name, $context);
        $previewContext = $provider->getComponentContext($settings->preview);
        // validate $settings->preview or default to 'component-library/_previews/default'
        return $this->renderTemplate($settings->preview, [
            ...$previewContext,
            'yield' => $html,
        ]);
    }
}
