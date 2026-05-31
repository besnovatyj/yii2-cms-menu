<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\controllers\backend;

class MenuController extends \yii\web\Controller
{
    public function actionIndex(): string
    {
        return $this->render('index');
    }

}
