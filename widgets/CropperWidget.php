<?php
/**
 *
 */

namespace mirkhamidov\fileupload\widgets;


class CropperWidget extends  \yii\base\Widget
{
    /**
     * Пропорции кропа
     * @var string|bool
     */
    public $cropAspect = '4 / 3';

    /**
     * @var null
     */
    public $modelClass = null;


    /** @var null  */
    public $model = null;

    public $attribute = null;

    /**
     * Возможность вращения
     * @var bool
     */
    public $rotation = true;

    /** @var bool */
    public $showButton = true;

    /** @var bool */
    public $showPreview = true;

    /** @var string|null */
    public $uploadCallback = null;

    /**
     *
     */
    public function run()
    {
        return $this->render('cropper', [
            'aspect' => $this->cropAspect,
            'rotation' => $this->rotation,
            'modelClass' => $this->modelClass,
            'model' => $this->model,
            'showButton' => $this->showButton,
            'showPreview' => $this->showPreview,
            'uploadCallback' => $this->uploadCallback,
            'attribute' => $this->attribute,
        ]);
    }
}
