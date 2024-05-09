<?php

namespace madebyraygun\componentlibrary\assetbundles;

use craft\web\AssetBundle;

class LibraryBrowserAssets extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = "@madebyraygun/componentlibrary/assetbundles/dist";
        $this->depends = [];

        $this->css = [
            'css/main.css',
        ];

        $this->js = [
            'js/index.js',
        ];

        parent::init();
    }
}
