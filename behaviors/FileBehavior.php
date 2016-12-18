<?php

namespace mirkhamidov\fileupload\behaviors;

use mirkhamidov\fileupload\models\File;
use mirkhamidov\fileupload\models\FileUploadForm;
use mirkhamidov\fileupload\models\queries\FileQuery;
use mirkhamidov\fileupload\ModuleTrait;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidValueException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class FileBehavior
 * @package mirkhamidov\fileupload\behaviors
 *
 * @mixin ModuleTrait
 */
class FileBehavior extends Behavior
{
    use ModuleTrait;

    const LOG_CATEGORY = 'file/fileBahavior';

    const IDENTIFIER = 'FileBehaviorIdentifier';

    /**
     * @var string|object
     */
    public $modelClassName;

    /**
     * @var array
     */
    public $attributesName = [];

    /**
     * @var array
     */
    private $defaultAttributesName = [
        'files' => [
            'rules' => [
                'maxFiles' => 3,
                'mimeTypes' => '*', // All files
                'maxSize' => 1024 * 1024 * 10 // 10 MB
            ],
        ],
    ];

    /** @inheritdoc */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'saveUploads',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveUploads',
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteUploads'
        ];
    }

    /** @inheritdoc */
    public function attach($owner)
    {
        if (Yii::$app instanceof \yii\console\Application) {
            $this->owner = $owner;
        } else {
            parent::attach($owner);
        }
    }

    /** @inheritdoc */
    public function init()
    {
        if (empty($this->attributesName)) {
            $this->attributesName = $this->defaultAttributesName;
        }
        parent::init();
    }

    /** @inheritdoc */
    public function __set($name, $value)
    {
        if (isset($this->attributesName[$name])) {
            // @todo
        } else {
            parent::__set($name, $value);
        }
    }

    /** @inheritdoc */
    public function __get($name)
    {
        if (isset($this->attributesName[$name])) {
            // @todo
            return $this->getDbFiles($name);
        }
        return parent::__get($name);
    }

    /** @inheritdoc */
    public function canGetProperty($name, $checkVars = true)
    {
        if (isset($this->attributesName[$name])) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars);
    }

    /** @inheritdoc */
    public function canSetProperty($name, $checkVars = true)
    {
        if (isset($this->attributesName[$name])) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * После загрузки связывать с родителем
     */
    public function saveUploads()
    {
        $uploadedFileModel = new FileUploadForm();
        if ($uploadedFileModel->load(Yii::$app->request->post())) {
            if (!empty($uploadedFileModel->uploaded)) {
                foreach ($uploadedFileModel->uploaded as $attributeName => $items) {
                    if (!isset($items[$this->getOwnerShortClassName()])) {
                        continue;
                    }
                    for ($max = count($items[$this->getOwnerShortClassName()]), $i = 0; $i < $max; ++$i) {
                        $curr = $items[$this->getOwnerShortClassName()][$i];
                        if (empty($curr)) {
                            continue;
                        }
                        $fileModel = File::findOne($curr);
                        if ($fileModel) {
                            $fileModel->entity_attribute = $attributeName;
                            $fileModel->entity = $this->getOwnerShortClassName();
                            $fileModel->entity_id = $this->owner->primaryKey;
                            if (!$fileModel->save()) {
                                $this->logError($fileModel->firstErrors);
                            }
                        }
                    }

                    $this->limitedUploads($attributeName, $this->owner->primaryKey);
                }
            }
        }
    }

    public function saveLocalFile($filePath, $attributeName = 'files')
    {
        if (!isset($this->attributesName[$attributeName])) {
            throw new InvalidValueException(\Yii::t('app', 'Invalid attribute name'));
        }

        if (is_file($filePath)) {
            $fileInfo = pathinfo($filePath);
            $fileHash = md5($fileInfo['basename'] . microtime());
            $toDir = $this->module->getFilesDirPath($fileHash);
            $newFilePath = $toDir . DIRECTORY_SEPARATOR . $fileHash . '.' . $fileInfo['extension'];

            if (copy($filePath, $newFilePath)) {
                $fileModel = new File();
                $fileModel->hash = $fileHash;
                $fileModel->file_name = $fileInfo['filename'];
                $fileModel->file_size = filesize($filePath);
                $fileModel->file_mime = mime_content_type($filePath);
                $fileModel->file_extension = $fileInfo['extension'];
                $fileModel->entity_attribute = $attributeName;
                $fileModel->entity = $this->getOwnerShortClassName();

                if (!$this->owner->isNewRecord) {
                    $fileModel->entity_id = $this->owner->primaryKey;
                }

                if ($fileModel->save()) {
                    return $fileModel;
                } else {
                    $this->owner->addErrors($fileModel->firstErrors);
                }
            }
        }

        return null;
    }

    /**
     * Если у сущности есть ограничение по кол-ву загружаемых файлов
     * @param $attributeName string
     * @param $entityId int
     * @return boolean
     */
    private function limitedUploads($attributeName, $entityId)
    {
        if (!empty($this->attributesName[$attributeName]['rules']['maxFiles'])) {
            $_need = File::find()
                ->where([
                    'entity' => $this->getOwnerShortClassName(),
                    'entity_attribute' => $attributeName,
                    'entity_id' => $entityId,
                ])
                ->limit(1)
                ->scalar();
            if ($_need) {
                $query = Yii::$app->db->createCommand();
                $query->update(
                    File::tableName(),
                    [
                        'is_deleted' => true,
                    ],
                    'entity=:entityName AND entity_attribute=:entityAttributeName AND entity_id=:entity_id AND id != :NOTNEED',
                    [
                        ':entityName' => $this->getOwnerShortClassName(),
                        ':entityAttributeName' => $attributeName,
                        ':entity_id' => $entityId,
                        ':NOTNEED' => $_need,
                    ]
                );

                return (bool)$query->execute();
            }

            return true;
        }
        return null;
    }

    /**
     * @return int
     */
    public function deleteUploads()
    {
        $cmd = Yii::$app->db->createCommand();
        $cmd->update(
            File::tableName(),
            ['is_deleted' => true],
            [
                'entity' => $this->getOwnerShortClassName(),
                'entity_id' => $this->owner->primaryKey,
            ]
        );
        return $cmd->execute();
    }

    /**
     * @param $attributeName
     * @return bool
     */
    public function getFileAttributeRules($attributeName)
    {
        if (isset($this->attributesName[$attributeName])
            && !empty($this->attributesName[$attributeName]['rules'])
        ) {
            return $this->attributesName[$attributeName]['rules'];
        }
        return false;
    }


    /**
     * @param $attributeName
     * @return array|bool
     */
    public function getFileConfig($attributeName)
    {
        if (isset($this->attributesName[$attributeName])) {
            return ArrayHelper::merge(
                ['entity' => $this->getOwnerShortClassName()],
                $this->attributesName[$attributeName]
            );
        }
        return false;
    }

    /**
     * @param string $attributeName
     * @return array|\mirkhamidov\fileupload\File[]|null
     */
    private function getDbFiles($attributeName)
    {
        if ($this->owner->isNewRecord) {
            return null;
        }
        $loadParams = [
            'entity_id' => $this->owner->primaryKey,
            'entity' => $this->getOwnerShortClassName(),
            'entity_attribute' => $attributeName,
        ];

        /** @var FileQuery $File */
        $File = File::find()->andWhere($loadParams)->onlyActive();



        if (!empty($this->attributesName[$attributeName]['rules']['maxFiles'])
            && $this->attributesName[$attributeName]['rules']['maxFiles'] == 1
        ) {
            return $File->one();
        }
        return $File->all();
    }

    /**
     * Название правильной "связи" с файлами из родительской модели
     * @return string
     */
    public function getFileEntityName()
    {
        return $this->getOwnerShortClassName();
    }


    /**
     * Получить короткое имя класса для привязки
     * @return string
     */
    private function getOwnerShortClassName()
    {
        $class = $this->modelClassName;
        if (empty($class)) {
            $class = get_class($this->owner);
        }
        return $this->getModule()->makeShortClassName($class);
    }

    /**
     * @param string|array $message
     */
    private function logError($message)
    {
        if (is_array($message)) {
            $message = implode('; ', $message);
        }
        Yii::error($message, self::LOG_CATEGORY);
    }
}