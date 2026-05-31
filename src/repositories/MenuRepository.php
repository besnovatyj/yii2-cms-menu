<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\repositories;

use Besnovatyj\Menu\entities\Menu;
use Besnovatyj\Menu\repositories\NotFoundException;

class MenuRepository
{
    public function get($id): Menu
    {
        if (!$menu = Menu::findOne($id)) {
            throw new NotFoundException('Menu is not found.');
        }
        return $menu;
    }

    /**
     * Получение всего дерева.
     * @return Menu[] Список узлов
     */
    public function getTree(): array
    {
        return Menu::find()
            ->orderBy(['tree' => SORT_ASC, 'lft' => SORT_ASC])
            ->all();
    }

    /**
     * Получение поддерева по slug.
     * @param string $slug Slug корневого узла
     * @return array Список дочерних узлов
     */
    public function getSubtreeBySlug(string $slug): array
    {
        $root = Menu::find()->where(['slug' => $slug])->one();
        if (!$root) {
            return [];
        }
        return Menu::find()
            ->where(['tree' => $root->tree])
            ->andWhere(['>', 'lft', $root->lft])
            ->andWhere(['<', 'rgt', $root->rgt])
            ->andWhere(['status' => 1])
            ->orderBy(['lft' => SORT_ASC])
            ->all();
    }

}
