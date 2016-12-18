<?php

namespace mirkhamidov\fileupload\models\queries;


/**
 * This is the ActiveQuery class for [[\mirkhamidov\fileupload\models\base\FileCache]].
 *
 * @see mirkhamidov\fileupload\models\base\FileCache
 */
class FileCacheQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return \mirkhamidov\fileupload\models\base\FileCache[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \mirkhamidov\fileupload\models\base\FileCache|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
