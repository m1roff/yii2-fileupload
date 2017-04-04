<?php
/**
 *
 */

namespace mirkhamidov\fileupload\widgets;


/**
 * Class CropperWidget
 * @package mirkhamidov\fileupload\widgets
 *
 *
 * @property string $modalId
 * @property string $cropperId
 */
class CropperWidget extends  \yii\base\Widget
{
    const CROP_ASPECT_16_9 = 16 / 9;
    const CROP_ASPECT_4_3 = 4 / 3;
    const CROP_ASPECT_1_1 = 1 / 1;
    const CROP_ASPECT_2_3 = 2 / 3;
    /**
     * Пропорции кропа
     * @var string|false
     */
    public $cropAspect = self::CROP_ASPECT_4_3;

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
     * @var bool Вкл. копирование ссылки при клике на фото
     */
    public $onClickCopy = false;

    /**
     *
     */
    public function run()
    {
        return $this->render('cropper');
    }

    public function getModalId()
    {
        return $this->id . '-add-photo-modal';
    }


    public function getCropperId()
    {
        return 'cropper_' . $this->getId();
    }
}
