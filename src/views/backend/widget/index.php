<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\TreeManager\Manager\TreeDataSource;
use Besnovatyj\TreeManager\Manager\TreeWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/**
 * @var View $this
 * @var string $title
 * @var TreeDataSource $treeDataSource
 */

$this->title = $title;
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="menu-tree-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>

        <div class="btn-group">
            <?= Html::a(
                '<i class="bi bi-list-ul"></i> Список',
                ['/Menu/backend/menu/index'],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?= TreeWidget::widget([
                'dataSource' => $treeDataSource,
                'endpoints' => [
                    'loadChildren' => Url::to(['/Menu/backend/widget/load-children']),
                    'createNode' => Url::to(['/Menu/backend/widget/create']),
                    'updateNode' => Url::to(['/Menu/backend/widget/update']),
                    'deleteNode' => Url::to(['/Menu/backend/widget/delete']),
                    'moveNode' => Url::to(['/Menu/backend/widget/move']),
                    'toggleStatus' => Url::to(['/Menu/backend/widget/toggle-status']),
                    'checkIntegrity' => Url::to(['/Menu/backend/widget/check-integrity']),
                ],
                'serverForms' => [
                    'enabled' => true,
                    'display' => 'modal',
                    'errorStrategy' => 'both',
                    'operations' => [
                        'create' => true,
                        'edit' => true,
                    ],
                    'getFormUrl' => Url::to(['/Menu/backend/widget/get-form']),
                ],
                'permissions' => [
                    'canCreate' => true, // Yii::$app->user->can('menu.create'),
                    'canUpdate' => true, // Yii::$app->user->can('menu.update'),
                    'canDelete' => true, // Yii::$app->user->can('menu.delete'),
                    'canMove' => true, // Yii::$app->user->can('menu.move'),
                ],
                'titleField' => 'title',
                'enablePersistence' => true,
                'storageKey' => 'menu-tree-state',
                'containerOptions' => [
                    'class' => 'menu-tree-widget',
                ],
            ]) ?>
        </div>
    </div>
</div>

<?php
// Дополнительные стили
$this->registerCss(<<<CSS
.menu-tree-widget {
    min-height: 400px;
}
CSS
);
?>
