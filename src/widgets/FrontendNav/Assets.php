<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\widgets\FrontendNav;

use yii\web\AssetBundle;

/**
 * Ассеты виджета меню.
 *
 * Здесь ТОЛЬКО то, без чего меню не работает: вложенные уровни (Bootstrap 5
 * их не поддерживает намеренно). Ни самого Bootstrap, ни оформления пунктов
 * бандл не тянет — это зона ответственности темы, поэтому и `depends` пуст:
 * добавь сюда `BootstrapPluginAsset` — и тема, подключающая свою сборку
 * Bootstrap, получит вторую копию его JS.
 *
 * Тема, которая рисует меню целиком своими стилями и скриптами, снимает бандл
 * сразу после регистрации:
 * `unset($this->view->assetBundles[Assets::class])` в `init()` после `parent::init()`.
 */
class Assets extends AssetBundle
{
    public $sourcePath = __DIR__ . '/media';

    public $css = [
        'frontend.css',
    ];

    public $js = [
        'frontend.js',
    ];

}
