<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Menu\widgets\add\assets;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = __DIR__ . '/../media/dist';

    public $js = [
        'index.js',
    ];
}
