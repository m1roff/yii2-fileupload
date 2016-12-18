<?php

namespace mirkhamidov\fileupload\models\base;

use mirkhamidov\fileupload\models\queries\FileQuery;
use Yii;

/**
 * This is the model class for table "{{%file}}".
 *
 * @property integer $id
 * @property string $entity
 * @property integer $entity_id
 * @property string $entity_attribute
 * @property string $hash
 * @property string $file_name
 * @property integer $file_size
 * @property string $file_mime
 * @property string $file_extension
 * @property string $params_data
 * @property boolean $is_deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property FileCache[] $fileCaches
 */
class File extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entity_id', 'file_size'], 'integer'],
            [['hash'], 'required'],
            [['params_data'], 'string'],
            [['is_deleted'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            [['entity', 'entity_attribute', 'file_name', 'file_mime'], 'string', 'max' => 255],
            [['hash'], 'string', 'max' => 32],
            [['file_extension'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'entity' => 'Связанняа модель',
            'entity_id' => 'ID связанной модели',
            'entity_attribute' => 'Атрибут связанной модели',
            'hash' => 'Идентификатор в файловой системе',
            'file_name' => 'Реальное название файла',
            'file_size' => 'Размер файла',
            'file_mime' => 'Тип файла',
            'file_extension' => 'Расширение файла',
            'params_data' => 'Params Data',
            'is_deleted' => 'Is Deleted',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFileCaches()
    {
        return $this->hasMany(FileCache::className(), ['file_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return FileQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FileQuery(get_called_class());
    }
}
