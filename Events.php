<?php

namespace humhub\modules\nucleus;

use Yii;
use yii\helpers\Url;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\admin\permissions\ManageModules;

/**
 * Event handler for the CoreModuleInstaller module
 */
class Events
{
    /**
     * Adds menu item to the admin menu
     * 
     * @param \yii\base\Event $event
     */
    public static function onAdminMenuInit($event)
    {
        if (!Yii::$app->user->can(ManageModules::class)) {
            return;
        }

        /** @var AdminMenu $menu */
        $menu = $event->sender;

        $menu->addItem([
            'label' => Yii::t('NucleusModule.base', 'Nucleus Settings'),
            'url' => Url::to(['/nucleus/admin/index']),
            'icon' => Icon::get('download'),
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'nucleus' && Yii::$app->controller->id == 'admin'),
            'sortOrder' => 500,
        ]);
    }
}