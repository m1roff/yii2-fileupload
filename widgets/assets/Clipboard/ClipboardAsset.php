<?php

namespace mirkhamidov\fileupload\widgets\assets\Clipboard;


use yii\web\AssetBundle;

/**
 * Class ClipboardAsset
 * @package common\widgets\FileUpload\assets\Clipboard
 */
class ClipboardAsset extends  AssetBundle {

    public $sourcePath = __DIR__;

    public $js = [
        'js/clipboard.min.js',
    ];

    public $css = [
    ];

}
