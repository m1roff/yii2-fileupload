<?php

namespace mirkhamidov\fileupload\assets;


use yii\web\AssetBundle;

class FileUploadAssets extends AssetBundle
{
    public $sourcePath = (__DIR__).'/upload';

    public $css = [
        'css/css.css',
    ];
    public $js = [
        'js/js.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}