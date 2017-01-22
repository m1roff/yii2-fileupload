<?php
/**
 * Модуль загрузки файлов на сервер.
 *
 * Пример использования:
 *
 *  Install module
 *      'fileupload' => [
 *          'class' => '\mirkhamidov\fileupload\Module',
 *      ],
 *
 *  Форма загрузки файла:
 *      <?=FileInputWidget::widget([
 *          'model' => $model,
 *          'attribute' => 'appeal_image',
 *      ])?>
 *
 *  Для показа загруженных: (в админке)
 *      <?=FileInputWidget::widget([
 *          'model' => $model,
 *          'attribute' => 'appeal_image',
 *          'onlyList' => true,
 *          'allowEdit' => true,
 *      ])?>
 *
 *  Чтобы работал превью-кроп
 *      <?= CropperWidget::widget([
 *          'modelClass' => 'News',
 *          'model' => $model,
 *          'attribute' => 'thumb',
 *          'cropAspect' => '1 / 1',
 *      ]) ?>
 *
 *  В связываемой модели (через поведение):
 *      public function behaviors()
 *      {
 *          return [
 *              ...
 *              FileBehavior::IDENTIFIER => [
 *                  'class' => FileBehavior::class,
 *                  'modelClassName' => Appeal::className(), // в БД пишет сокращенное название класса
 *                  'attributesName' => [                    // По умолчанию будет доступен file атрибут
 *                      'appeal_image' => [                  // Параметр модели, которые будет привзяан к файлам
 *                          'rules' => [                     // Правила валидации для yii\validators\FileValidator
 *                              'maxFiles' => 5,             // так же передается виджету и при привышении лимита удаляются (софт) файлы в БД
 *                              'skipOnEmpty' => false,
 *                              'mimeTypes' => 'image/*',
 *                              'maxSize' => 1024 * 1024 * 50, // 50 MB
 *                              'extensions' => ["jpg", "png", "gif", "jpeg", 'svg'],  //так же передается виджету
 *                          ],
 *                      ],
 *                  ],
 *              ],
 *              ...
 *      }
 */
namespace mirkhamidov\fileupload;

use mirkhamidov\fileupload\models\File;
use mirkhamidov\fileupload\models\FileUploadForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\validators\FileValidator;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * file module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'mirkhamidov\fileupload\controllers';

    public $storePath = '@app/uploads/store';

    public $rules = [];

    public $tableName = 'attach_file';

    public $uploadFilesRoute = '/fileupload/default/upload';

    public $deleteFilesRoute = '/fileupload/default/delete';

    public $paramsFilesRoute = '/fileupload/default/params';

    public $saveFilesRoute = '/fileupload/default/save';

    /**
     * @var string Выводится с помощью [[sprintf()]]
     */
    public $uploadedUrlFormat = '/flf%s';

    /**
     * For previewSettings & [generateInitialPreview()]
     * @var array
     */
    public $previewFileTypes = [
        'image' => [],
        'html' => [],
        'text' => [],
        'video' => [],
        'audio' => [],
        'flash' => [],
        'object' => [],
        'other' => [],
    ];

    /** @var array */
    const CASCADES = [
//        '@common/components/facedetect/haarcascade_frontalface_alt.xml',
//        '@common/components/facedetect/haarcascade_frontalface_alt2.xml',
//        '@common/components/facedetect/haarcascade_frontalface_default.xml',
//        '@common/components/facedetect/haarcascade_profileface.xml',
//        '@common/components/facedetect/haarcascade_smile.xml',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->defaultRoute = 'file';
    }

    /**
     * @param object|string $obj
     * @return string
     */
    public function makeShortClassName($obj)
    {
        if (is_object($obj)) {
            $classNameWithNamespace = get_class($obj);
        } else {
            $classNameWithNamespace = $obj;
        }
        return substr($classNameWithNamespace, strrpos($classNameWithNamespace, '\\') + 1);
    }

    /**
     * @param $fileHash
     * @return string
     */
    public function getFilesDirPath($fileHash)
    {
        $path = $this->getStorePath() . DIRECTORY_SEPARATOR . $this->getSubDirs($fileHash);

        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * @param $fileHash
     * @param int $depth
     * @return string
     */
    public function getSubDirs($fileHash, $depth = 4)
    {
        $depth = min($depth, 9);
        $path = '';

        for ($i = 0; $i < $depth; $i++) {
            $folder = substr($fileHash, $i * 3, 2);
            $path .= $folder;
            if ($i != $depth - 1) {
                $path .= DIRECTORY_SEPARATOR;
            }
        }

        return $path;
    }

    /**
     * @return bool|string
     */
    public function getStorePath()
    {
        return \Yii::getAlias($this->storePath);
    }

    /**
     * @param File|File[] $fileModel
     */
    public function generateInitialPreview($fileModel)
    {
        $result = [];

        if (empty($fileModel)) {
            return $result;
        }

        if (!ArrayHelper::isIndexed($fileModel)) {
            $fileModel = [$fileModel];
        }



        if (!empty($fileModel)) {
            for ($i = 0, $max = count($fileModel); $i < $max; ++$i) {
                switch ($fileModel[$i]->getPreviewFileType()) {
                    case 'text':
                        $result[] = iconv('windows-1251', 'utf-8', file_get_contents($fileModel[$i]->getRootPath()));
                        break;

                    default:
                        $result[] = $fileModel[$i]->getFileUrl();
                        break;
                }

            }
        }

        return $result;
    }

    /**
     * @param File|File[] $fileModel
     */
    public function generateInitialPreviewConfig($fileModel)
    {
        $result = [];
        if (empty($fileModel)) {
            return $result;
        }

        $makeConfig = function (File $fileModel) {
            $_deleteRoute = $this->deleteFilesRoute;
            if (!is_array($_deleteRoute)) {
                $_deleteRoute = [$this->deleteFilesRoute];
            }
            return [
                'caption' => $fileModel->realFileNameFull,
                'size' => $fileModel->file_size,
                'url' => Url::to(array_merge($_deleteRoute, ['id' => $fileModel->primaryKey])),
                'key' => $fileModel->primaryKey,
                'filetype' => $fileModel->file_mime,
                'type' => $fileModel->getPreviewFileType(),
            ];
        };


        if (is_array($fileModel)) {
            for ($i = 0, $max = count($fileModel); $i < $max; ++$i) {
                $result[] = $makeConfig($fileModel[$i]);
            }
        } else {
            $result[] = $makeConfig($fileModel);
        }
        return $result;
    }

    /**
     * Обнаружение лиц
     * @param string $filePath rootPath
     * @return array|null
     */
    public function detectFaces($filePath)
    {
        $faces = [];
        if (!function_exists('face_detect')) {
            return $faces;
        }

        foreach (self::CASCADES AS $cascade) {
            $detected = face_detect($filePath, Yii::getAlias($cascade));
            $faces = ArrayHelper::merge($faces, $detected);
        }

        if (empty($faces)) {
            return null;
        }
        return $faces;
    }

    public function storeLocal($path)
    {
        $extensions = explode('/', mime_content_type($path));
        $extensions = isset($extensions[1]) ? $extensions[1] : $extensions[0];

        $fileHash = md5(Yii::$app->security->generateRandomString() . microtime());

        $filePath = $this->getFilesDirPath($fileHash) . DIRECTORY_SEPARATOR . $fileHash . '.' . $extensions;
        copy($path, $filePath);
        $fileModel = new File();
        $fileModel->hash = $fileHash;
        $fileModel->file_name = uniqid('upload-');
        $fileModel->file_size = filesize($path);
        $fileModel->file_mime = mime_content_type($filePath);
        $fileModel->file_extension = $extensions;
        if (!$fileModel->save()) {
            var_dump($fileModel->getErrors());
        }

        return $fileModel;
    }

    public function saveFile()
    {

        $model = new FileUploadForm();
        $file = UploadedFile::getInstances($model, 'file');

        $model->file = $file;
        $result = [];

        foreach ($model->file AS $file) {

            $extensions = explode('/', mime_content_type($file->tempName));
            $extensions = isset($extensions[1]) ? $extensions[1] : $extensions[0];

            $fileHash = md5(Yii::$app->security->generateRandomString() . microtime());

            $filePath = $this->getFilesDirPath($fileHash) . DIRECTORY_SEPARATOR . $fileHash . '.' . $extensions;

            if ($file->saveAs($filePath)) {
                $fileModel = new File();
                $fileModel->hash = $fileHash;
                $fileModel->file_name = uniqid('upload-');
                $fileModel->file_size = $file->size;
                $fileModel->file_mime = mime_content_type($filePath);
                $fileModel->file_extension = $extensions;
                if (!$fileModel->save()) {
                    $model->addErrors($fileModel->firstErrors);
                } else {
                    $result['file'] = $fileModel->getFileUrl();
                    $result['id'] = $fileModel->id;
                }
            } else {
                $model->addErrors($file->error);
            }
        }
        if ($model->hasErrors()) {
            $result['error'] = implode("\n", $model->firstErrors);
        }

        return $result;
    }

    /**
     * Возвращает:
     *  [
     *      "initialPreview" => [
     *          "//HOSTNAME/uploads/store/95/79/2b/8f/9557952b68ff6789c20308fce27ebfab.png", // URL файла
     *      ],
     *      "initialPreviewConfig" => [
     *          [
     *              "caption" => "b2.png",                  // реальное имя файла
     *              "size" => 180113,                       // размер файла
     *              "url" => "/fileupload/default/delete?id=3",   // ссылка для удаления файла
     *              "key" => 3,                             // ID записи
     *          ]
     *      ],
     *      "uploadedFiles" => [
     *          3, // ID записи
     *      ],
     *  ]
     * @param UploadedFile[] $file
     * @param FileUploadForm $model
     * @param array $postData
     * @return array
     * @throws InvalidConfigException
     */
    public function uploadFile($file, FileUploadForm $model, array $postData)
    {
        $result = [];

        if (empty($postData['entity']) || empty($postData['entity_attribute'])) {
            throw new InvalidValueException('Не указаны необходимые параметры (entity, entity_attribute).');
        }

        $entityModel = Yii::createObject($postData['entity']);
        if (!$entityModel) {
            throw new InvalidConfigException('Неудалось иницилизировать объект.');
        }

        $filedConfig = $entityModel->getFileConfig($postData['entity_attribute']);

        // Проверка требований к файлу
        if (!$file) {
            $model->addError('file', 'Ошибка загрузки. Проверьте требования к загружаемым файлам.');
        } elseif (!empty($filedConfig['rules'])) {
            if (!empty($filedConfig['rules']['maxFiles']) && $filedConfig['rules']['maxFiles'] == 1) {
                $result['append'] = false;
            }
            $validator = new FileValidator($filedConfig['rules']);
            if (!$validator->validate($file[0], $errors)) {
                $model->addError('file', $errors);
            }
        }

        $model->file = $file;

        if (!$model->hasErrors() && $model->file && $model->validate()) {
            $result['initialPreview'] = [];
            $result['initialPreviewConfig'] = [];

            foreach ($model->file as $file) {
                $fileInfo = pathinfo($file->name);
                $fileHash = md5(Yii::$app->security->generateRandomString() . microtime());

                $filePath = $this->getFilesDirPath($fileHash) . DIRECTORY_SEPARATOR . $fileHash . '.' . $fileInfo['extension'];;

                if ($file->saveAs($filePath)) {
                    $fileModel = new File();
                    $fileModel->hash = $fileHash;
                    $fileModel->file_name = $fileInfo['filename'];
                    $fileModel->file_size = $file->size;
                    $fileModel->file_mime = mime_content_type($filePath);
                    $fileModel->file_extension = $fileInfo['extension'];;
                    if (!$fileModel->save()) {
                        $model->addErrors($fileModel->firstErrors);
                    } else {
                        $result['uploadedFiles'][] = $fileModel->primaryKey;
                        $result['initialPreview'] = $this->generateInitialPreview($fileModel);
                        $result['initialPreviewConfig'] = $this->generateInitialPreviewConfig($fileModel);
                        $result['initialPreviewShowDelete'] = true;
                    }
                } else {
                    $model->addErrors($file->error);
                }
            }
        }

        if ($model->hasErrors()) {
            $result['error'] = implode("\n", $model->firstErrors);
        }

        return $result;
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function deleteFile($id)
    {
        $model = File::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Указан неверный ID');
        } elseif ($model->is_deleted) {
            return [
                'status' => 'already_deleted',
            ];
        } else {
            $model->is_deleted = true;
            if ($model->save()) {
                $result['status'] = 'success';
                $result['deleted'] = $model->primaryKey;
            } else {
                $result['status'] = 'fail';
                $result['error'] = $model->firstErrors;
            }
        }

        return $result;
    }
}
