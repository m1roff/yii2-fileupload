<?php
/**
 *
 */

namespace mirkhamidov\fileupload\widgets\assets;


use mirkhamidov\fileupload\widgets\assets\Clipboard\ClipboardAsset;
use mirkhamidov\fileupload\widgets\assets\Cropper\CropperAssets;
use mirkhamidov\fileupload\widgets\assets\UploadAsset\UploadAsset;
use yii\web\AssetBundle;

class CropperWidgetAssets extends AssetBundle
{
    public $depends = [
        CropperAssets::class,
        UploadAsset::class,
        ClipboardAsset::class,
    ];
}
