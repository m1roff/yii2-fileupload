<?php

use mirkhamidov\fileupload\models\File;
use mirkhamidov\fileupload\widgets\assets\CropperWidgetAssets;
use mirkhamidov\fileupload\widgets\CropperWidget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/** @var View $this */
/** @var bool $rotation */
/** @var bool $showButton */

/** @var \yii\db\ActiveRecord $model */

CropperWidgetAssets::register($this);

/** @var CropperWidget $widget */
$widget = $this->context;

$_jsConfig = [
    'modalId' => $widget->modalId,
    'aspect' => $widget->cropAspect,
    'modelClass' => $widget->modelClass,
    'attribute' => $widget->attribute,
    'showPreview' => $widget->showPreview,
    'uploadCallback' => $widget->uploadCallback,
];

$this->registerJs('var cropper_config_' . $widget->cropperId . '=' . Json::encode($_jsConfig), View::POS_BEGIN);




?>

<div class="cropper-main-container">

    <?php if ($widget->showButton): ?>
        <div class="form-group">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#<?= $widget->modalId ?>">
                Добавить фото
            </button>
        </div>
    <?php endif; ?>
    <?php if ($widget->showPreview): ?>
        <div class="form-group">
            <div class="cropper-uploaded-photos" data-cropper-id="<?= $widget->cropperId ?>">

                <?php
                if ($widget->model && $widget->model->{$widget->attribute}) {
                    $_viewFile = function ($file) {
                        /** @var $file File */
                        return Html::img($file->getFileUrl('150'), [
                            'class' => 'cropper-upload-preview-item',
                            'data' => [
                                'clipboard-text' => $file->getFileUrl('150'),
                                'imageid' => $file->id,
                            ],
                            'alt' => 'PHOTO',
                            'title' => 'Кликните для копирования ссылки',
                            'width' => '150',
                        ]);
                    };

                    if ($widget->model->{$widget->attribute} instanceof File) {
                        echo $_viewFile($widget->model->{$widget->attribute});
                    } else {
                        foreach($widget->model->{$widget->attribute} AS $file) {
                            echo $_viewFile($file);
                        }
                    }
                };
                ?>
                <br>
                <div class="small">
                    Клик на фото копирует ссылку в буфер обмена
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="modal upload_custom fade" id="<?= $widget->modalId ?>" data-copper-id="<?= $widget->cropperId ?>" tabindex="-1" role="dialog"
         aria-labelledby="addPhotoModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addPhotoModalLabel">Добавление фотографии <?= $widget->attribute ?></h4>
                </div>
                <div class="modal-body">
                    <div>
                        <?= Html::fileInput('files-' . $widget->id . '[]', null, [
                            'class' => 'hidden cropper-files',
                            'data' => ['cropper-id' => $widget->cropperId],
                        ]) ?>

                        <?= Html::button('Выбрать файл', [
                            'class' => 'btn cropper-select-photo',
                            'data' => ['cropper-id' => $widget->cropperId],
                        ]) ?>

                        <?= Html::tag('div', null, [
                            'class' => 'cropper-images-list', // #list
                            'data' => ['cropper-id' => $widget->cropperId],
                        ]) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="btn-group pull-left">
                        <?php if ($widget->rotation): ?>
                            <?= Html::button('<i class="fa fa-undo" aria-hidden="true"></i>', [
                                'class' => 'btn btn-default rotate-left pull-left',
                                'data' => [
                                    'cropper-id' => $widget->cropperId,
                                    'toggle' => 'tooltip',
                                    'animation' => 'false',
                                    'original-title' => Yii::t('app', 'Rotate Left (-90)'),
                                ],
                            ]) ?>

                            <?= Html::button('<i class="fa fa-repeat" aria-hidden="true"></i>', [
                                'class' => 'btn btn-default rotate-right pull-left',
                                'data' => [
                                    'cropper-id' => $widget->cropperId,
                                    'toggle' => 'tooltip',
                                    'animation' => 'false',
                                    'original-title' => Yii::t('app', 'Rotate Right (90)'),
                                ],
                            ]) ?>
                        <?php endif; ?>
                    </div>

                    <div class="btn-group pull-left">
                        <?= Html::button('<i class="fa fa-search-plus" aria-hidden="true"></i>', [
                            'class' => 'btn btn-default zoom-in pull-left',
                            'data' => [
                                'cropper-id' => $widget->cropperId,
                                'toggle' => 'tooltip',
                                'animation' => 'false',
                                'original-title' => Yii::t('app', 'Zoom In'),
                            ],
                        ]) ?>

                        <?= Html::button('<i class="fa fa-search-minus" aria-hidden="true"></i>', [
                            'class' => 'btn btn-default zoom-out pull-left',
                            'data' => [
                                'cropper-id' => $widget->cropperId,
                                'toggle' => 'tooltip',
                                'animation' => 'false',
                                'original-title' => Yii::t('app', 'Zoom Out'),
                            ],
                        ]) ?>
                    </div>

                    <div class="btn-group pull-left">
                        <?= Html::button('<i class="fa fa-refresh" aria-hidden="true"></i>', [
                            'class' => 'btn btn-default set-to-initial pull-left',
                            'data' => [
                                'cropper-id' => $widget->cropperId,
                                'toggle' => 'tooltip',
                                'animation' => 'false',
                                'original-title' => Yii::t('app', 'From begining'),
                            ],
                        ]) ?>
                    </div>




                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <?= Html::button('Добавить', [
                        'class' => 'btn btn-primary cropper-add-file-btn',
                        'data' => [
                            'cropper-id' => $widget->cropperId,
                            'modal-id' => $widget->modalId,
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>


    <div class="cropper-uploaded-files" data-cropper-id="<?= $widget->cropperId ?>"></div>

</div>
