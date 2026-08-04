<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Menu\forms\backend\MenuItemForm;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Json;
use yii\web\View;

/* @var $this View */
/* @var $model MenuItemForm */
/* @var $catalog array<string,array{label:string,targets:array,candidates:array}> */
/* @var $parents array<int,string> */

$this->title = 'Добавить пункт меню из модулей';
$this->params['breadcrumbs'][] = ['label' => 'Меню', 'url' => ['/Menu/backend/widget/index']];
$this->params['breadcrumbs'][] = $this->title;

$moduleItems = [];
foreach ($catalog as $moduleId => $data) {
    $moduleItems[$moduleId] = $data['label'];
}

$rootId = 'menu-target-form';
?>

<?php $form = ActiveForm::begin(['action' => ['create']]); ?>
<div class="card" id="<?= $rootId ?>" data-catalog='<?= Json::htmlEncode($catalog) ?>'>
    <div class="card-header"><?= Html::encode($this->title) ?></div>
    <div class="card-body">

        <?php if ($catalog === []): ?>
            <div class="alert alert-warning">
                Ни один модуль не объявил целей для меню. Установите/включите модуль, реализующий
                <code>MenuTargetProvider</code> (например, Documents), чтобы добавлять пункты меню из
                категорий/разделов. Произвольную ссылку можно добавить виджетом «Добавить в меню» на
                странице модуля.
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12 col-md-4">
                <label class="form-label" for="menu-target-module">Модуль</label>
                <select class="form-select" id="menu-target-module" data-role="module">
                    <option value="">— выберите модуль —</option>
                    <?php foreach ($moduleItems as $id => $label): ?>
                        <option value="<?= Html::encode((string)$id) ?>"><?= Html::encode((string)$label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="menu-target-route">Цель</label>
                <select class="form-select" id="menu-target-route" data-role="route">
                    <option value="">— выберите цель —</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="menu-target-candidate">Раздел / категория</label>
                <select class="form-select" id="menu-target-candidate" data-role="candidate">
                    <option value="">— выберите раздел —</option>
                </select>
            </div>
        </div>

        <hr>

        <?= $form->field($model, 'parentId')->dropDownList(
            $parents,
            ['prompt' => '— корневой узел —']
        )->label('Родительский пункт меню') ?>

        <?= $form->field($model, 'name')->textInput(['data-role' => 'name']) ?>
        <?= $form->field($model, 'name_addon')->textInput() ?>
        <?= $form->field($model, 'url')->textInput(['data-role' => 'url'])
            ->hint('Заполняется автоматически по выбранной цели; при необходимости можно поправить вручную.') ?>
        <?= $form->field($model, 'slug')->textInput(['data-role' => 'slug']) ?>
        <?= $form->field($model, 'active_string')->textInput(['data-role' => 'active_string'])
            ->hint('Часть URL, при совпадении с которой пункт подсвечивается активным. По умолчанию — сам URL.') ?>
        <?= $form->field($model, 'encode')->checkbox() ?>
        <?= $form->field($model, 'status')->dropDownList([1 => 'Вкл.', 0 => 'Выкл.']) ?>

    </div>
    <div class="card-footer clearfix">
        <?= Html::submitButton('Создать пункт меню', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Отмена', ['/Menu/backend/widget/index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>

<?php
// Каскад модуль → цель → кандидат целиком на встроенных данных, без запросов к серверу и доп. ассетов.
// Выбор кандидата предзаполняет name / url / slug / active_string (пустые поля не перетираются).
$js = <<<'JS'
(function () {
    var root = document.getElementById('menu-target-form');
    if (!root) { return; }
    var catalog = JSON.parse(root.getAttribute('data-catalog') || '{}');

    var moduleSel = root.querySelector('[data-role="module"]');
    var routeSel = root.querySelector('[data-role="route"]');
    var candidateSel = root.querySelector('[data-role="candidate"]');
    var nameInput = root.querySelector('[data-role="name"]');
    var urlInput = root.querySelector('[data-role="url"]');
    var slugInput = root.querySelector('[data-role="slug"]');
    var activeInput = root.querySelector('[data-role="active_string"]');

    function reset(select, prompt) {
        select.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = prompt;
        select.appendChild(opt);
    }

    function targetsOf(moduleId) {
        var mod = catalog[moduleId];
        return (mod && mod.targets) ? mod.targets : [];
    }

    function candidatesOf(moduleId, route) {
        var mod = catalog[moduleId];
        return (mod && mod.candidates && mod.candidates[route]) ? mod.candidates[route] : [];
    }

    function fillRoutes() {
        reset(routeSel, '— выберите цель —');
        targetsOf(moduleSel.value).forEach(function (t) {
            var o = document.createElement('option');
            o.value = t.route;
            o.textContent = t.label;
            routeSel.appendChild(o);
        });
        fillCandidates();
    }

    function fillCandidates() {
        reset(candidateSel, '— выберите раздел —');
        candidatesOf(moduleSel.value, routeSel.value).forEach(function (c) {
            var o = document.createElement('option');
            o.value = c.slug;
            o.textContent = c.label;
            o.setAttribute('data-url', c.url);
            o.setAttribute('data-label', c.label);
            candidateSel.appendChild(o);
        });
    }

    function applyCandidate() {
        var opt = candidateSel.options[candidateSel.selectedIndex];
        if (!opt || !opt.value) { return; }
        var url = opt.getAttribute('data-url') || '';
        var label = opt.getAttribute('data-label') || '';
        if (nameInput && nameInput.value.trim() === '') { nameInput.value = label; }
        if (urlInput) { urlInput.value = url; }
        if (slugInput && slugInput.value.trim() === '') { slugInput.value = opt.value; }
        if (activeInput && activeInput.value.trim() === '') { activeInput.value = url; }
    }

    moduleSel.addEventListener('change', fillRoutes);
    routeSel.addEventListener('change', fillCandidates);
    candidateSel.addEventListener('change', applyCandidate);
})();
JS;
$this->registerJs($js, View::POS_END);
