<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Contracts\adminMenu\AdminMenuLocation;
use Besnovatyj\Contracts\adminMenu\AdminMenuPlacement;

return [
    // Menu-tree
    [
        'label' => 'Menu-tree',
        'iconClass' => 'bi bi-menu-button me-1',
        'url' => ['/Menu/backend/widget/index'],
        'active' => static function () {
            return str_contains(Yii::$app->request->url, 'Menu/backend/widget/index');
        },
        '_meta' => [
            'placements' => [
                new AdminMenuPlacement(
                    location: AdminMenuLocation::RightSidebar,
                    group: 'Меню',
                    groupIcon: 'bi bi-menu-button',
                    groupPriority: 100,
                    priority: 100,
                ),
            ],
        ],
    ],
    // Добавление пункта меню из целей, объявленных модулями (MenuTargetProvider)
    [
        'label' => 'Добавить из модулей',
        'iconClass' => 'bi bi-plus-square me-1',
        'url' => ['/Menu/backend/target/index'],
        'active' => static function () {
            return str_contains(Yii::$app->request->url, 'Menu/backend/target');
        },
        '_meta' => [
            'placements' => [
                new AdminMenuPlacement(
                    location: AdminMenuLocation::RightSidebar,
                    group: 'Меню',
                    groupIcon: 'bi bi-menu-button',
                    groupPriority: 100,
                    priority: 110,
                ),
            ],
        ],
    ],
];
