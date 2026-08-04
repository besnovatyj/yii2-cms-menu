<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\readModels;

use Besnovatyj\Menu\entities\Menu;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQuery;

class MenuReadRepository
{
    public function count(): int
    {
        return Menu::find()->active()->count();
    }

    public function getAllByRange($offset, $limit): array
    {
        return Menu::find()->active()->orderBy(['id' => SORT_ASC])->limit($limit)->offset($offset)->all();
    }

    public function getAll(): DataProviderInterface
    {
        $query = Menu::find()->active()->orderBy(['pinned' => SORT_DESC])->with(['taxonomy', 'tags']);
        return $this->getProvider($query);
    }

    public function find($id): ?Menu
    {
        $post = Menu::find()->active()->andWhere(['id' => $id])->one();
        if ($post instanceof Menu) {
            return $post;
        }
        return null;
    }

    /**
     * Плоский список всех узлов дерева для выпадающего списка родителя (id => имя с отступом по глубине).
     * Без фильтра активности — родителем может быть и черновой узел.
     *
     * @return array<int,string>
     */
    public function selectOptions(): array
    {
        $options = [];
        $nodes = Menu::find()->orderBy(['tree' => SORT_ASC, 'lft' => SORT_ASC])->all();
        foreach ($nodes as $node) {
            $indent = $node->depth > 0 ? str_repeat('— ', (int)$node->depth) : '';
            $options[(int)$node->id] = $indent . $node->name;
        }
        return $options;
    }

    private function getProvider(ActiveQuery $query): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
//            'sort' => false,
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC]
            ]
        ]);
    }
}
