<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\entities;

use Besnovatyj\TreeManager\Manager\entities\Node;
use Besnovatyj\Menu\entities\queries\MenuQuery;

/**
 * Модель пункта меню
 *
 * @property int $id
 * @property int $tree - Номер дерева.
 * @property int $lft - Левый ключ.
 * @property int $rgt - Правый ключ.
 * @property int $depth - Глубина.
 * @property string $name - Название пункта меню.
 * @property string $name_addon - Дополнение к названию пункта меню.
 * @property int $encode - Декодировать ли HTML сущности в названии пункта меню.
 * @property string $url - Ссылка на которую ведёт пункт меню.
 * @property string $active_string - Строка, при совпадении с которой пункт меню будет активен.
 * @property int $status - Статус активности пункта меню.
 * @property string $slug - Уникальный идентификатор пункта меню. У корней уникален.У листьев - $root->slug + '#' + $this->slug
 * @property int $sort_order - Порядок сортировки корневых узлов
 */
class Menu extends Node
{

    public const int STATUS_DRAFT = 0;
    public const int STATUS_ACTIVE = 1;

    /**
     * Factory method для создания пункта меню
     */
    public static function create(
        string $name,
        string $name_addon,
        int    $encode,
        string $url,
        string $slug,
        int    $status,
        string $active_string,
    ): self
    {
        $menu = new static();
        $menu->name = $name;
        $menu->name_addon = $name_addon;
        $menu->encode = $encode;
        $menu->url = $url;
        $menu->slug = $slug;
        $menu->status = $status;
        $menu->active_string = $active_string;
        return $menu;
    }

    /**
     * Редактирование пункта меню
     */
    public function edit(
        string $name,
        string $name_addon,
        int    $encode,
        string $url,
        int    $status,
        string $slug,
        string $active_string,
    ): void
    {
        $this->name = $name;
        $this->name_addon = $name_addon;
        $this->encode = $encode;
        $this->url = $url;
        $this->status = $status;
        $this->slug = $slug;
        $this->active_string = $active_string;
    }

    public static function tableName(): string
    {
        return '{{%menu_menus}}';
    }

    public static function find(): MenuQuery
    {
        return new MenuQuery(static::class);
    }

}
