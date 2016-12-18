<?php

namespace mirkhamidov\fileupload\models;


use mirkhamidov\fileupload\ModuleTrait;
use yii\base\Model;

class FileUploadForm extends Model
{
    use ModuleTrait;

    public $file;

    public $uploaded;

    public function rules()
    {
        return [
            [['file', 'uploaded'], 'safe'],
        ];
    }
}