<?php

namespace mirkhamidov\fileupload;


trait ModuleTrait
{
    /**
     * @var null|Module
     */
    private $_module = null;

    /**
     * @return null|Module
     * @throws \Exception
     */
    protected function getModule()
    {
        if ($this->_module == null) {
            $this->_module = \Yii::$app->getModule('fileupload');
        }

        if (!$this->_module) {
            throw new \Exception("Модуль mirkhamidov\\fileupload не найдет, проверьте конфигурацию.");
        }

        return $this->_module;
    }
}