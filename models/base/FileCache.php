<?php

namespace mirkhamidov\fileupload\models\base;

use mirkhamidov\fileupload\models\queries\FileCacheQuery;
use Yii;

/**
 * This is the model class for table "{{%file_cache}}".
 *
 * @property integer $id
 * @property integer $file_id
 * @property string $hash
 * @property string $url
 * @property string $created_at
 *
 * @property File $file
 */
class FileCache extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file_cache}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['file_id', 'hash', 'url'], 'required'],
            [['file_id'], 'integer'],
            [['created_at'], 'safe'],
            [['hash', 'url'], 'string', 'max' => 255],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::className(), 'targetAttribute' => ['file_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file_id' => 'File ID',
            'hash' => 'Hash',
            'url' => 'Url',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::className(), ['id' => 'file_id']);
    }

    /**
     * @inheritdoc
     * @return \mirkhamidov\fileupload\models\queries\FileCacheQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileCacheQuery(get_called_class());
    }
}
