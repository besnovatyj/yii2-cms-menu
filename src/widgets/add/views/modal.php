<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

use Besnovatyj\Menu\forms\backend\MenuItemForm;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Json;

/** @var string        $modalId Уникальный ID модального окна (по $this->id виджета) */
/** @var MenuItemForm  $model   Предзаполненная форма */
/** @var array         $config  Конфиг для TypeScript: endpoint + csrfHeaders */

?>

<button type="button"
        class="btn btn-secondary"
        data-bs-toggle="modal"
        data-bs-target="#<?= Html::encode($modalId) ?>">
    Добавить в меню
</button>

<div class="modal fade add-menu-item-widget"
     id="<?= Html::encode($modalId) ?>"
     tabindex="-1"
     aria-labelledby="<?= Html::encode($modalId) ?>Label"
     aria-hidden="true"
     data-config="<?= Html::encode(Json::encode($config)) ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= Html::encode($modalId) ?>Label">
                    Добавить в меню
                </h5>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(['options' => ['class' => 'form']]); ?>
                <?= $form->field($model, 'nodeId')->hiddenInput()->label(false) ?>
                <?= $form->field($model, 'parentId')->hiddenInput()->label(false) ?>
                <?= $form->field($model, 'name')->textInput() ?>
                <?= $form->field($model, 'name_addon')->textInput() ?>
                <?= $form->field($model, 'encode')->checkbox() ?>
                <?= $form->field($model, 'url')->textInput() ?>
                <?= $form->field($model, 'active_string')->textInput() ?>
                <?= $form->field($model, 'slug')->textInput() ?>
                <?= $form->field($model, 'status')->dropDownList([1 => 'Вкл.', 0 => 'Выкл.']) ?>
                <div class="d-grid gap-2">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
