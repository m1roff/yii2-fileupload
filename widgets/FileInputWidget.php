<?php
/**
 * Расширение виджета kartik\file\FileInput
 * Examples: http://plugins.krajee.com/file-input-ajax-demo/10
 */
namespace mirkhamidov\fileupload\widgets;

use mirkhamidov\fileupload\ModuleTrait;
use mirkhamidov\fileupload\models\FileUploadForm;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\InputWidget;

class FileInputWidget extends InputWidget
{
    use ModuleTrait;

    /**
     * @var ActiveRecord Связываемая модель через Entity
     */
    public $model;

    /** @inheritdoc */
    public $language = 'ru';

    /**
     * @var string Название атрибута для связывания
     */
    public $attribute;

    /**
     * @var bool Показывать в виде списка (без возможности загрузки)
     */
    public $onlyList = false;

    /**
     * @var bool Возможность редактировать файл
     */
    public $allowEdit = false;

    /**
     * @var array Возможные действия редактирования
     */
    public $editActions = [
        'rotate' => true,
    ];


    /**
     * @var bool Автоматическая загрузка файлов
     */
    public $autoUplaod = true;

    /**
     * @var array
     */
    public $pluginEvents = [];

    /**
     * @var array
     */
    public $pluginOptions = [];

    /**
     * @var array
     */
    public $pluginOptionsDefault = [
        'initialPreviewAsData' => true,
        'overwriteInitial' => false,
        'browseOnZoneClick' => true,
        'showClose' => false,
        'showCaption' => false,
        'showUpload' => true,
        'showBrowse' => true,
        'showRemove' => true,
        'uploadAsync' => true,
        'minFilecount' => 0,
        'layoutTemplates' => [
            'actions' => "<div class=\"file-actions\">\n" .
                "<div class=\"file-footer-buttons\">\n{zoom} {other}</div>\n" .
                "<div class=\"file-upload-indicator\" title=\"{indicatorTitle}\">{indicator}</div>\n" .
                "<div class=\"clearfix\"></div>\n" .
                "</div>",
        ],
        'previewZoomSettings' => [
            'image' => ['width' => "auto", 'height' => "100%"],
        ],
        'previewSettings' => [
            'image' => [ 'width' => "200px", 'height' => "auto"],
        ],
        'otherActionButtons' => '',
        'dropZoneClickTitle' => '<br><span>Перетащите файлы</span>',
        'dropZoneTitle' => '<span class="glyphicon glyphicon-upload" style="font-size:200%;"></span>',
        'dropZoneEnabled' => true,
    ];

    /**
     * @var array
     */
    public $options = [
        'multiple' => true,
    ];

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        if (empty($this->model)) {
            throw new InvalidConfigException("Property {model} cannot be blank");
        }

        if (empty($this->attribute)) {
            throw new InvalidConfigException("Property {attribute} cannot be blank");
        }

        $this->pluginOptions = ArrayHelper::merge($this->pluginOptionsDefault, $this->pluginOptions);

        // Правила валидации (некоторые из них работают на фронте)
        $rules = $this->model->getFileAttributeRules($this->attribute);
        if ($rules) {
            if (!empty($rules['extensions'])) {
                $this->pluginOptions['allowedFileExtensions'] = $rules['extensions'];
            }


            if (isset($rules['maxFiles'])) {
                $this->pluginOptions['maxFileCount'] = $rules['maxFiles'];
            }
        }

        $this->pluginOptions['uploadUrl'] = Url::toRoute($this->getModule()->uploadFilesRoute);

        if (!$this->model->isNewRecord) {
            // Показ уже загруженных файлов
            $this->pluginOptions['initialPreview'] = $this->getModule()->generateInitialPreview($this->model->{$this->attribute});
            $this->pluginOptions['initialPreviewConfig'] = $this->getModule()->generateInitialPreviewConfig($this->model->{$this->attribute});
        }

        // Дополнительные данные для загрузки файлов
        $this->pluginOptions['uploadExtraData'] = [
            'entity' => $this->model->className(),
            'entity_attribute' => $this->attribute,
        ];

        if ($this->onlyList === true) {
            // Режим только просмотра
            $this->pluginOptions['dropZoneEnabled'] = false;
            $this->pluginOptions['defaultPreviewContent'] = '<i>файлов нет</i>';
        }

        if ($this->allowEdit === true) {
            // Возможность редактировать файл
            if ($this->editActions['rotate'] === true) {
                $this->pluginOptions['otherActionButtons'] .= '<button '
                    .' data-file-attribute="'.$this->attribute.'"'
                    .' data-params-url="'.Url::to($this->getModule()->paramsFilesRoute).'"'
                    .' onclick="jQuery(this).trigger(\'cp-file-rotate\')"'
                    .' class="cp-file-rotate btn btn-xs btn-default" {dataKey}><i class="glyphicon glyphicon-repeat"></i></button>';
                $this->pluginOptions['layoutTemplates']['actions'] = "<div class=\"file-actions\">\n" .
                    "<div class=\"file-footer-buttons\">\n{delete} {zoom} {other}</div>\n" .
                    "<div class=\"file-upload-indicator\" title=\"{indicatorTitle}\">{indicator}</div>\n" .
                    "<div class=\"clearfix\"></div>\n" .
                    "</div>";
            }
        }


        $this->options['class'] = 'cp-file-upload-form';

        if ($this->autoUplaod) {
            $this->pluginEvents['filebatchselected'] = 'function (event, files) {$(event.target).delay(3000).fileinput("upload"); }';
        }
        $this->pluginEvents['filepredelete'] = 'function () {return !confirm("Уверены, что хотите удалить файл?");}';
    }

    public function run()
    {
        return $this->render('view', [
            'model' => new FileUploadForm(),
            'attribute' => $this->attribute,
            'options' => $this->options,
            'pluginOptions' => $this->pluginOptions,
            'pluginEvents' => $this->pluginEvents,
            'allowEdit' => $this->allowEdit,
            'entityName' => $this->model->getFileEntityName(),
            'widgetId' => $this->id,
        ]);
    }
}