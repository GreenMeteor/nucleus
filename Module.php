<?php

namespace humhub\modules\nucleus;

use Yii;
use yii\helpers\Url;
use humhub\modules\nucleus\services\ModuleInstallerService;

/**
 * nucleus Module Definition
 */
class Module extends \humhub\components\Module
{
    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/nucleus/admin/index']);
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        Yii::$app->cache->flush();
        
        parent::disable();
    }
}