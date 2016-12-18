<?php
/**
 * Работать только с копией файла из initDuplicateOfOrigin()
 * Получить копию файла можно через getRootPath()
 * Получить URL копии файла можно через getFileUrl()
 * А хэш через getCopyHash()
 *
 *
 * Атрибут params:
 *  [
 *      'rotate' => Integer,
 *      'facesDetected' => Boolean,
 *      'facesDetectedResult' => Array, // Результат выполнения ф-ии detect_face
 *      'watermark' => Boolean,
 *      'watermarkFile' => String, // Путь до файла
 *      'crop' => Boolean,
 *      'cropParam' => [
 *          // минимум, необходимо указать height или width
 *          'width' => Integer,
 *          'height' => Integer,
 *          'x' => Integer,
 *          'y' => Integer,
 *          'focus' => String, // default:center, "northwest", "northeast", "southwest", "southeast"
 *      ],
 *  ]
 *
 */
namespace mirkhamidov\fileupload\models;

use mirkhamidov\fileupload\ModuleTrait;
use Imagick;
use Yii;
use mirkhamidov\fileupload\models\base\File as BaseFile;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * @property bool $isImage
 * @property array $params
 * @property string $fileNameFull
 * @property string $realFileNameFull
 * @property string $originRootPath
 * @property string $copyHash
 * @property string $currentHash
 * @property string $rootPath
 * @property bool $isFacesInited
 * @property bool $isEntityBinded
 * @property string $urlByHash
 * @property string $fileUrl
 * @property ActiveQuery $caches
 *
 * @mixin ModuleTrait
 */

class File extends BaseFile
{
    use ModuleTrait;

    const ACTION_ROTATE = 'rotate';

    const EDIT_ROTATE_STEP = 90;

    const DEFAULT_COPY = '_copy';

    /** @var null|Imagick $image *//** @var null|Imagick $image */
    private $image;

    /**
     * @var array
     */
    public $errors;

    /** @inheritdoc */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'timeStampBehavior' => [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
        ]);
    }

    /**
     * @return array|mixed
     */
    public function getParams()
    {
        $params = Json::decode($this->params_data);
        if (empty($params)) {
            $params = [];
        }
        return $params;
    }

    /**
     * @param $v
     */
    public function setParams($v)
    {
        $this->params_data = Json::encode(ArrayHelper::merge(
            Json::decode($this->params_data),
            $v
        ));
    }

    /**
     * @return string
     */
    public function getFileNameFull()
    {
        return $this->hash.'.'.$this->file_extension;
    }

    /**
     * @return string
     */
    public function getRealFileNameFull()
    {
        return $this->file_name.'.'.$this->file_extension;
    }

    /**
     * @return string
     */
    public function getOriginRootPath()
    {
        return $this->getModule()->getFilesDirPath($this->hash) . DIRECTORY_SEPARATOR . $this->hash .'.'. $this->file_extension;
    }

    /**
     * @return string
     */
    public function getCopyHash()
    {
        return md5($this->hash.self::DEFAULT_COPY);
    }

    /**
     * @param null $size
     * @return string
     */
    public function getCurrentHash($size = null)
    {
        return md5($this->hash.$size.$this->params_data);
    }

    /**
     * Получить путь к рабочему (дубликату) файлу
     * @param null $hash
     * @return string
     */
    public function getRootPath($hash = null)
    {
        if ($hash === null) {
            $hash = $this->getCopyHash();
            $this->initDuplicateOfOrigin();
        }
        $rootPath = $this->getModule()->getFilesDirPath($hash).DIRECTORY_SEPARATOR.$hash.'.'.$this->file_extension;
        return $rootPath;
    }

    /**
     * Инициализировать рабочую копию файла
     * @throws Exception
     */
    private function initDuplicateOfOrigin()
    {
        $hash = $this->getCopyHash();
        $rootPath = $this->getModule()->getFilesDirPath($hash).DIRECTORY_SEPARATOR.$hash.'.'.$this->file_extension;
        if (!is_file($rootPath)) {
            try {
                copy($this->originRootPath, $rootPath);
            } catch (\Exception $e) {
                throw new Exception('Не удалось создать Файл-дубликат.');
            }
        }
    }

    /**
     * @return bool
     */
    public function getIsFacesInited()
    {
        return !(!isset($this->params['facesDetected'])
            || $this->params['facesDetected'] === false);
    }

    /**
     * @return bool
     */
    public function getIsEntityBinded()
    {
        return !empty($this->entity_id);
    }

    /**
     * Ссылка на файл
     * @param null $hash
     * @return string
     * @throws \Exception
     */
    public function getUrlByHash($hash = null)
    {
        $hash = $hash ? $hash : $this->getCopyHash();
        $cache = FileCache::getUrlByHash($hash);
        if (!empty($cache)) {
            return $cache;
        }
        $url = '/' . $this->getModule()->getSubDirs($hash) . '/' . $hash . '.' . $this->file_extension;
        $_urlFormat = $this->getModule()->uploadedUrlFormat;
        if (!empty($_urlFormat)) {
            $url = sprintf($_urlFormat, $url);
        }
        return $url;
    }

    /**
     * Получит ссылку на изображение с применением всех действий записанных в params
     * @param null|string $size
     * @return string
     */
    public function getFileUrl($size = null, array $params = [])
    {
        $this->initDuplicateOfOrigin();

        if (!$this->isImage || !$this->isEntityBinded) {
            return $this->getUrlByHash();
        }

        if (!empty($params)) {
            $this->params = $params;
        }

        $thisHash = $this->getCurrentHash($size);

        $returnUrl = FileCache::getUrlByHash($thisHash);

        if (empty($returnUrl)) {
            $fileRootPath = $this->getRootPath($thisHash);
            $returnUrl = $this->getUrlByHash($thisHash);

            if (!is_file($fileRootPath)) {

                $this->image = new Imagick($this->getRootPath());

                // resize
                if ($size !== null) {
                    $this->editResize($size);
                }
                // END resize

                // rotate
                if (isset($this->params['rotate']) && $this->params['rotate'] > 0) {
                    $this->editRotate($this->params['rotate']);
                }
                // END rotate

                // watermark
                if (isset($this->params['watermark']) && $this->params['watermark'] === true) {
                    $wfile = null;
                    if (isset($this->params['watermarkFile'])) {
                        $wfile = $this->params['watermarkFile'];
                    }
                    $this->editAddWatermark($wfile);
                }
                // END watermark

                // crop
                if (isset($this->params['crop']) && $this->params['crop'] === true) {
                    $cWidth = isset($this->params['cropParam']['width']) ? $this->params['cropParam']['width'] : null;
                    $cHeight = isset($this->params['cropParam']['height']) ? $this->params['cropParam']['height'] : null;
                    $cropParams = [];
                    if (isset($this->params['cropParam']['x'])) {
                        $cropParams['x'] = $this->params['cropParam']['x'];
                    }
                    if (isset($this->params['cropParam']['y'])) {
                        $cropParams['y'] = $this->params['cropParam']['y'];
                    }
                    if (isset($this->params['cropParam']['focus'])) {
                        $cropParams['focus'] = $this->params['cropParam']['focus'];
                    }
                    $this->editCrop($cWidth, $cHeight, $cropParams);
                }
                // END crop

                if (!$this->image) {
                    $this->image = new Imagick($this->getRootPath());
                }
                $this->image->writeImage($fileRootPath);
                FileCache::saveIn($this->primaryKey, $thisHash, $returnUrl);
            } else {
                FileCache::saveIn($this->primaryKey, $thisHash, $returnUrl);
            }
        }

        return $returnUrl;

    }

    /**
     * @return bool
     */
    public function getIsImage()
    {
        return strpos($this->file_mime, 'image') !== false;
    }


    /**
     * Парсим размер
     * @param $notParsedSize
     * @return array|null
     * @throws \Exception
     */
    private function parseSize($notParsedSize)
    {
        $sizeParts = explode('x', $notParsedSize);
        $part1 = (isset($sizeParts[0]) and $sizeParts[0] != '');
        $part2 = (isset($sizeParts[1]) and $sizeParts[1] != '');
        if ($part1 && $part2) {
            if (intval($sizeParts[0]) > 0
                &&
                intval($sizeParts[1]) > 0
            ) {
                $size = [
                    'width' => intval($sizeParts[0]),
                    'height' => intval($sizeParts[1])
                ];
            } else {
                $size = null;
            }
        } elseif ($part1 && !$part2) {
            $size = [
                'width' => intval($sizeParts[0]),
                'height' => null
            ];
        } elseif (!$part1 && $part2) {
            $size = [
                'width' => null,
                'height' => intval($sizeParts[1])
            ];
        } else {
            throw new \Exception('Something bad with size, sorry!');
        }
        return $size;
    }

    /**
     * Инициализация лиц на текущем изображении
     * Не применяет полученные данные а записывает в params!
     * @param bool $force Принудительно (если ранее уже инициализировано)
     * @return $this
     */
    public function initFaces($force = false)
    {
        if (
            $force === true ||
            (!isset($this->params['facesDetected']) || $this->params['facesDetected'] === false)
        ) {
            $params = [];
            $faces = $this->getModule()->detectFaces($this->originRootPath);
            $params['facesDetected'] = true;
            $params['facesDetectedResult'] = $faces;
            $this->params = $params;
            $this->save(false);
            return $faces;
        } elseif (!empty($params['facesDetectedResult'])) {
            return $params['facesDetectedResult'];
        }
        return null;
    }

    /**
     * Поворот изображения
     * Не меняет изображение а записывает в params!
     * @return bool
     */
    public function actionRotate()
    {
        $params = $this->params;

        if (!$this->isImage) {
            return true;
        }

        if (empty($params['rotate'])) {
            $params['rotate'] = self::EDIT_ROTATE_STEP;
        } elseif (($params['rotate']+self::EDIT_ROTATE_STEP) > 270) {
            $params['rotate'] = 0;
        } else {
            $params['rotate'] += self::EDIT_ROTATE_STEP;
        }
        $this->params = $params;

        return $this->save(false);
    }

    /**
     * @return string
     */
    public function getPreviewFileType()
    {
        $_t = explode('/', $this->file_mime);
        if (isset($this->getModule()->previewFileTypes[$_t[0]])) {
            return $_t[0];
        }
        return 'other';
    }

    /**
     * Поворот картники
     * @param null|int $angle
     * @return bool|null
     */
    private function editRotate($angle = null)
    {
        try {
            if (!$this->image) {
                $this->image = new Imagick($this->getRootPath());
            }
            if ($angle) {
                $this->image->rotateImage(new \ImagickPixel('#00000000'), $angle);
                return true;
            }
            return null;
        } catch (\ImagickException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Замазка лиц
     * @param array $faces
     * @return bool|null
     */
    private function editHideFaces(array $faces)
    {
        if (!$this->isImage) {
            return true;
        }
        if (!empty($faces)) {
            try {
                if (!$this->image) {
                    $this->image = new Imagick($this->getRootPath());
                }
                foreach ($faces as $face) {
                    $imageCrop = new Imagick($this->getRootPath());
                    $imageCrop->setImageCompressionQuality(10);
                    $imageCrop->blurImage(10,10);
                    $imageCrop->cropImage($face['w'], $face['h'], $face['x'], $face['y']);
                    $this->image->compositeImage($imageCrop, Imagick::COMPOSITE_OVER, $face['x'], $face['y']);
                    $imageCrop->destroy();
                }

            } catch (\ImagickException $e) {
                $this->errors[] = $e->getMessage();
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }

            return true;
        }
        return null;
    }

    /**
     * Наложение watermark
     * @param null|string $filePath Если null то смотрит на Yii::$app->params['watermark']
     * @return bool
     */
    private function editAddWatermark($filePath = null)
    {
        try {
            if (!$this->image) {
                $this->image = new Imagick($this->getRootPath());
            }

            $wFile = null;
            $watermark = null;

            if ($filePath !== null) {
                $wFile = $filePath;
            } elseif (isset(Yii::$app->params['watermark'])) {
                $wFile = Yii::$app->params['watermark'];
            }

            if ($wFile !== null) {
                $watermark = new Imagick();
                $watermark->readImage($wFile);
            }

            if ($watermark) {
                $iWidth = $this->image->getImageWidth();
                $iHeight = $this->image->getImageHeight();
                $wWidth = $watermark->getImageWidth();
                $wHeight = $watermark->getImageHeight();

                if ($iHeight < $wHeight || $iWidth < $wWidth) {

                    $watermark->scaleImage($iWidth, $iHeight);

                    $wWidth = $watermark->getImageWidth();
                    $wHeight = $watermark->getImageHeight();
                }

                $x = ($iWidth - $wWidth) / 2;
                $y = ($iHeight - $wHeight) / 2;

                $this->image->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);

            } else {
                return false;
            }
        } catch (\ImagickException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return true;
    }

    /**
     * Кроп картники
     * @param null $needWidth
     * @param null $needHeight
     * @param array $needParams
     * @internal param null $w
     * @internal param null $h
     * @internal param null $x
     * @internal param null $y
     * @return bool
     */
    private function editCrop($needWidth = null, $needHeight = null, array $needParams = [])
    {
        if ($needWidth === null && $needHeight === null) {
            return null;
        }

        $defaultParams = [
            'focus' => 'center',
            'x' => null,
            'y' => null,
        ];
        $params = ArrayHelper::merge($defaultParams, $needParams);
        try {
            if (!$this->image) {
                $this->image = new Imagick($this->getRootPath());
            }

            $w = $this->image->getImageWidth();
            $h = $this->image->getImageHeight();

            if ($needWidth === null) {
                $needWidth = $w;
            }
            if ($needHeight === null) {
                $needHeight = $h;
            }

            if ($w > $h) {
                $resize_w = $w * $needHeight / $h;
                $resize_h = $needHeight;
            } else {
                $resize_w = $needWidth;
                $resize_h = $h * $needWidth / $w;
            }

            // Если нужно правильно кропать!
            $this->image->resizeImage($resize_w, $resize_h, Imagick::FILTER_LANCZOS, 0.9);
            if ($params['x'] !== null && $params['y'] !== null) {
                $x = $params['x'];
                $y = $params['y'];
            } else {

                switch ($params['focus']) {
                    case 'northwest':
                        $x = $y = 0;
                        break;
                    case 'center':
                        $x = ($resize_w - $needWidth) / 2;
                        $y = ($resize_h - $needHeight) / 2;
                        break;

                    case 'northeast':
                        $x = $resize_w - $needWidth;
                        $y = 0;
                        break;

                    case 'southwest':
                        $x = 0;
                        $y = $resize_h - $needHeight;
                        break;

                    case 'southeast':
                        $x = $resize_w - $needWidth;
                        $y = $resize_h - $needHeight;
                        break;
                    default:
                        $x = $y = 0;
                        break;
                }
            }

            $this->image->cropImage($needWidth, $needHeight, $x, $y);

            return true;
        } catch (\ImagickException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Ресайз картники
     * @param null|array $rsize
     * @return bool|null
     * @throws \Exception
     * @throws \yii\base\Exception
     */
    private function editResize($rsize = null)
    {
        $size = null;
        if ($rsize !== null) {
            $size = $this->parseSize($rsize);
        }

        try {
            if (!$this->image) {
                $this->image = new \Imagick($this->getRootPath());
            }
            $this->image->setImageCompressionQuality(100);
            if ($size) {
                if ($size['height'] && $size['width']) {
                    $this->image->cropThumbnailImage($size['width'], $size['height']);
                } elseif ($size['height']) {
                    $this->image->thumbnailImage(0, $size['height']);
                } elseif ($size['width']) {
                    $this->image->thumbnailImage($size['width'], 0);
                } else {
                    throw new \Exception('Something wrong with parseSize($rsize)');
                }
            } else {
                return null;
            }

        } catch (\ImagickException $e) {
            $this->errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return true;
    }





    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCaches()
    {
        return $this->hasMany(FileCache::className(), ['file_id' => 'id']);
    }
}
