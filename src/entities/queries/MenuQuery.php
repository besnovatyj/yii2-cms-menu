<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\entities\queries;

use Besnovatyj\Menu\entities\Menu;
use yii\db\ActiveQuery;

class MenuQuery extends ActiveQuery
{
    /**
     * @param null $alias
     * @return $this
     */
    public function active($alias = null): static
    {
        return $this->andWhere([
            ($alias ? $alias . '.' : '') . 'status' => Menu::STATUS_ACTIVE,
        ]);
    }
}
