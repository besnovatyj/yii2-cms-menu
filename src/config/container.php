<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Menu\entities\Menu;
use Besnovatyj\TreeManager\Manager\entities\Node;
use Besnovatyj\TreeManager\Manager\forms\TreeNodeFormInterface;
use Besnovatyj\TreeManager\Manager\TreeManager;
use Besnovatyj\TreeManager\Manager\TreeQueryScope;

/**
 * Конфигурация DI контейнера для модуля Menu
 */
return function (\yii\di\Container $container): void {

    $container->setSingleton('menu.tree.manager', function () use ($container) {
        return new TreeManager(
            modelClass: Menu::class,
            entityFactory: function (TreeNodeFormInterface $form): Menu {
                return Menu::create(
                    name: $form->name,
                    name_addon: $form->name_addon,
                    encode: $form->encode,
                    url: $form->url,
                    slug: $form->slug,
                    status: $form->status,
                    active_string: $form->active_string,
                );
            },
            entityUpdater: function (Node $node, TreeNodeFormInterface $form): Node {
                /** @var Menu $node */
                $node->edit(
                    name: $form->name,
                    name_addon: $form->name_addon,
                    encode: $form->encode,
                    url: $form->url,
                    status: $form->status,
                    slug: $form->slug,
                    active_string: $form->active_string,
                );
                return $node;
            },
        );
    });
    $container->setSingleton('menu.tree.scope', function () use ($container) {
        return new TreeQueryScope(Menu::class);
    });
};
