<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Menu\forms\backend\MenuItemForm;
use yii\bootstrap5\ActiveForm;

/**
 * Веб-форма, которая используется в двух виджетах:
 */
/** @var $model MenuItemForm */

?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->errorSummary($model) ?>
<?php
if ($model->parentId !== null) {
    echo $form->field($model, 'parentId')->hiddenInput()->label(false);
}
?>
<?= $form->field($model, 'nodeId')->hiddenInput()->label(false) ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= $form->field($model, 'name_addon')->textInput() ?>
<?= $form->field($model, 'encode')->checkbox() ?>
<?= $form->field($model, 'url')->textInput() ?>
<?= $form->field($model, 'active_string')->textInput() ?>
<?= $form->field($model, 'slug')->textInput() ?>
<?= $form->field($model, 'status')->dropDownList([true => 'Вкл.', false => 'Выкл.']) ?>
<?php ActiveForm::end(); ?>
