<?php

namespace mirkhamidov\fileupload\widgets\assets\UploadAsset;

use yii\bootstrap\BootstrapAsset;
use yii\bootstrap\BootstrapPluginAsset;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Class UploadAsset
 * @package common\widgets\FileUpload\assets\UploadAsset
 */
class UploadAsset extends AssetBundle
{
    public $sourcePath = __DIR__;

    public $js = [
        'js/js.js'
    ];

    public $css = [
        'css/style.css',
    ];

    public $depends = [
        JqueryAsset::class,
        BootstrapAsset::class,
        BootstrapPluginAsset::class,
    ];
}
