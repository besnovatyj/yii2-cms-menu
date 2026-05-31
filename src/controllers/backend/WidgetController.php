<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\controllers\backend;

use Besnovatyj\TreeManager\Manager\controllers\TreeController;
use Besnovatyj\TreeManager\Manager\TreeDataSource;
use Besnovatyj\Menu\entities\Menu;
use Besnovatyj\Menu\forms\backend\MenuItemForm;
use Yii;

/**
 * Контроллер для управления деревом меню
 */
class WidgetController extends TreeController
{
    public function __construct($id, $module, $config = [])
    {
        $this->treeManager = Yii::$container->get('menu.tree.manager');
        $this->dataSource = new TreeDataSource(
            Menu::class,
            function (Menu $model) {
                return [
                    'id' => $model->id,
                    'title' => $model->name,
                    'url' => $model->url,
                    'slug' => $model->slug,
                ];
            },
            'sort_order'
        );
        $this->createFormClass = MenuItemForm::class;
        $this->updateFormClass = MenuItemForm::class;
        $this->formView = '_form';
        $this->indexTitle = 'Управление меню';
        parent::__construct($id, $module, $config);
    }
}
