<?php

namespace mirkhamidov\fileupload\models\queries;

use mirkhamidov\fileupload\models\base\File;

/**
 * This is the ActiveQuery class for [[\common\modules\file\models\base\File]].
 *
 * @see \common\modules\file\models\base\File
 */
class FileQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return File[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return File|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function onlyActive()
    {
        return $this->andWhere(['is_deleted'=>false]);
    }
}
