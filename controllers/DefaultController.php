<?php
/**
 * Контроллер для работы с загружаемыми файлами на сервер
 */

namespace mirkhamidov\fileupload\controllers;

use mirkhamidov\fileupload\models\File;
use mirkhamidov\fileupload\models\FileUploadForm;
use mirkhamidov\fileupload\ModuleTrait;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class DefaultController extends Controller
{
    use ModuleTrait;

    public $defaultAction = 'upload';

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'upload' => ['post'],
                    'delete' => ['post'],
                    'save' => ['post'],
                    'params' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Загрузка файла. Даже если указано несколько файлов на загрузку, все равно загружается по одной \
     *  (один файл - один запрос на загрузку)
     *
     * @return array
     */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new FileUploadForm();
        $file = UploadedFile::getInstances($model, 'file');

        $postData = Yii::$app->request->post();

        return $this->getModule()->uploadFile($file, $model, $postData);
    }

    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->getModule()->saveFile();
    }

    /**
     * @param $id
     * @return array
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->getModule()->deleteFile($id);
    }

    /**
     * Обновление параметров
     * @return array
     */
    public function actionParams()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $result = ['status' => 'default'];

        $fileId = Yii::$app->request->post('id', null);
        $fileAction = Yii::$app->request->post('action', null);
        if (empty($fileId)) {
            $result['status'] = 'fail';
            $result['message'] = 'Не передан ключ.';
            return $result;
        }

        $fileModel = File::findOne($fileId);

        if (!$fileModel) {
            $result['status'] = 'fail';
            $result['message'] = 'Данного файла не существует.';
            return $result;
        }

        if ($fileAction == File::ACTION_ROTATE) {
            $fileModel->actionRotate();
        }

        $result['status'] = 'success';
        $result['url'] = $fileModel->getFileUrl();


        return $result;
    }
}