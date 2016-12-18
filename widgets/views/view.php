<?php
/** @var array $options */
/** @var array $pluginOptions */
/** @var array $pluginEvents */
/** @var string $attribute */
/** @var boolean $allowEdit */
/** @var string $entityName */
/** @var string $widgetId */
/** @var mirkhamidov\fileupload\models\FileUploadForm $model */

use kartik\file\FileInput;
use mirkhamidov\fileupload\assets\FileUploadAssets;
use yii\helpers\Html;

FileUploadAssets::register(Yii::$app->view);

?>
<div class="form-group file-upload-form">

    <?=FileInput::widget([
        'model' => $model,
        'attribute' => 'file',
        'resizeImages' => false,
        'language' => 'ru',
        'showMessage' => true,
        'pluginLoading' => true,
        'sortThumbs' => false,
        'pluginEvents' => $pluginEvents,
        'pluginOptions' => $pluginOptions,
        'options' => $options,
    ])?>

    <?= Html::activeHiddenInput($model, 'uploaded['.$attribute.']['.$entityName.'][]', [
        'class' => 'cp-file-uploaded-list',
        'id' => $widgetId . '-upload-list',
    ])?>

    <?php if ($allowEdit) : ?>
    <?= Html::activeHiddenInput($model, 'uploaded['.$attribute.']['.$entityName.'][]', [
        'class' => 'cp-file-options',
            'id' => $widgetId . '-file-options',
    ])?>
    <?php endif ?>
</div>
