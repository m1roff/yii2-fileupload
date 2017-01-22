<?php

namespace mirkhamidov\fileupload\widgets\assets\Cropper;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 *
 * Class FileApiAssets
 * @package common\widgets\FileUpload\assets\FileApi
 */
class CropperAssets extends AssetBundle
{
    public $sourcePath = __DIR__;

    public $js = [
        'js/cropper.min.js',
    ];

    public $css = [
        'css/cropper.min.css',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}
