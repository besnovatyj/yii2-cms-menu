<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\migrations;

use common\components\migration\BaseMigration;
use yii\base\NotSupportedException;

/** 'm<YYMMDD_HHMMSS>_<Name>' */
class m241012_180250_create_menu_table extends BaseMigration
{
    public const string TABLE_NAME = '{{%menu_menus}}';

    /**
     * @throws NotSupportedException
     */
    public function safeUp(): void
    {
        parent::safeUp();

        if ($this->existTable(static::TABLE_NAME)) {
            return;
        }

        $this->createTable(static::TABLE_NAME, [
            'id' => $this->primaryKey()
                ->comment('PK'),
            'tree' => $this->integer(10)->null() // TODO Кажется, при переносе веток между деревьями обнуляется, проверить
            ->comment('Идентификатор дерева, если разрешено несколько деревьев (Form Paulzi behavior).'),
            'lft' => $this->integer(10)->notNull()
                ->comment('Левый ключ NestedSets.'),
            'rgt' => $this->integer(10)->notNull()
                ->comment('Правый ключ NestedSets.'),
            'depth' => $this->integer(10)->notNull() // Атрибут не может быть беззнаковым!
            ->comment('Глубина NestedSets.'),
            'name' => $this->string(255)->notNull()
                ->comment('Название пункта меню.'),
            'name_addon' => $this->string(255)->null()
                ->comment('Дополнение к названию пункта меню.'),
            'encode' => $this->smallInteger(1)->notNull()->defaultValue(1)
                ->comment('Декодировать ли HTML сущности в названии пункта меню.'),
            'url' => $this->string(255)->notNull()
                ->comment('Ссылка на которую ведёт пункт меню.'),
            'active_string' => $this->string(255)->notNull()->defaultValue('')
                ->comment('Строка, при совпадении с которой пункт меню будет активен.'),
            'status' => $this->string(255)->notNull()->defaultValue(0)
                ->comment('Статус активности пункта меню.'),
            'slug' => $this->string(255)->notNull()->unique()
                ->comment('Уникальный идентификатор пункта меню.'),
            'sort_order' => $this->integer(10)->notNull()->defaultValue(0)
                ->comment('Сортировка корней'),
        ], $this->tableOptions);
        $this->addCommentOnTable(static::TABLE_NAME, 'Модуль меню');

        $this->createIndexes(static::TABLE_NAME, ['slug'], false, true);
        $this->createIndexes(static::TABLE_NAME, ['depth']);
        $this->createIndexes(static::TABLE_NAME, ['tree', 'rgt']);
        $this->createIndexes(static::TABLE_NAME, ['tree', 'lft', 'rgt']);


        parent::safeUp();
    }

    public function safeDown(): void
    {
        parent::safeDown();
    }

}
