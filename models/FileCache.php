<?php

namespace mirkhamidov\fileupload\models;

use Yii;
use mirkhamidov\fileupload\models\base\FileCache as BaseFileCache;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

class FileCache extends BaseFileCache
{
    /** @inheritdoc */
    public function behaviors()
    {
        return [
            [
                'class'              => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value'              => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * Записать данные о созданном файле
     * @param $fileId
     * @param $hash
     * @param $url
     * @return bool
     */
    public static function saveIn($fileId, $hash, $url)
    {
        $model = new FileCache();
        $model->hash = $hash;
        $model->url = $url;
        $model->file_id = $fileId;
        return $model->save();
    }

    /**
     * @param $hash
     * @return false|null|string
     */
    public static function getUrlByHash($hash)
    {
        return FileCache::find()->select('url')->where(['hash'=>$hash])->scalar();
    }

    /**
     * @param $hash
     * @return null|static
     */
    public static function getByHash($hash)
    {
        return FileCache::findOne(['hash'=>$hash]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::className(), ['id' => 'file_id']);
    }
}
