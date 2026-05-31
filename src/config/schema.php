<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

return [
    'tables' => [
        // https://www.yiiframework.com/doc/api/2.0/yii-db-querybuilder#getColumnType()-detail
        // @see \yii\db\QueryBuilder::getColumnType()
        '{{%menu_menus}}' => [
            'columns' => [
                'id' => 'pk',
                'tree' => 'integer DEFAULT NULL',
                'lft' => 'integer NOT NULL',
                'rgt' => 'integer NOT NULL',
                'depth' => 'integer NOT NULL', // Атрибут не может быть беззнаковым!
                'name' => 'string NOT NULL',
                'name_addon' => 'string NULL DEFAULT NULL',
                'encode' => 'tinyint(1) NOT NULL DEFAULT 1',
                'url' => 'string NOT NULL',
                'active_string' => 'string NOT NULL DEFAULT \'\'',
                'status' => 'tinyint(1) NOT NULL DEFAULT 0',
                'slug' => 'string NOT NULL',
                'sort_order' => 'integer NOT NULL DEFAULT 0',
            ],
            'comments' => [ // Комментарии к столбцам
                'id' => '(Form Paulzi behavior)',
                'tree' => 'Идентификатор дерева, если разрешено несколько деревьев (Form Paulzi behavior).',
                'lft' => 'Левый ключ NestedSets (Form Paulzi behavior)',
                'rgt' => 'Правый ключ NestedSets (Form Paulzi behavior)',
                'depth' => 'Глубина NestedSets (Form Paulzi behavior)',
                'name' => 'Название пункта меню',
                'name_addon' => 'Дополнение к названию пункта меню',
                'encode' => 'Декодировать ли HTML сущности в названии пункта меню',
                'url' => 'Ссылка на которую ведёт пункт меню',
                'active_string' => 'Строка, при совпадении с которой пункт меню будет активен',
                'status' => 'Статус активности пункта меню',
                'slug' => 'Уникальный идентификатор пункта меню',
                'sort_order' => 'Сортировка корней',
            ],
            'comment' => 'Модуль меню', // Комментарий к таблице
            'indexes' => [
                // [['column_1', 'column_2', ...], isUnique(false), isPK(false)]
                ['depth', false],
                [['tree', 'rgt'], false],
                [['tree', 'lft', 'rgt'], false],
                ['slug', true], // true - уникальный индекс
            ],
        ],

    ],
    'initialData' => [],
];
