<?php

use humhub\widgets\TopMenu;
use humhub\modules\nucleus\Module;
use humhub\modules\nucleus\Events;
use humhub\modules\admin\widgets\AdminMenu;

return [
    'id' => 'nucleus',
    'class' => Module::class,
    'namespace' => 'humhub\modules\nucleus',
    'events' => [
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
    ],
];