<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\widgets\FrontendNav;

use Throwable;
use yii\bootstrap5\Html;
use yii\bootstrap5\Dropdown;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

/**
 * Выпадающие уровни меню — БАЗОВАЯ разметка Bootstrap 5, без классов конкретной темы.
 *
 * Рекурсивен: `renderItems()` зовёт `static::widget()`, поэтому обслуживает всю глубину
 * дерева, а не только второй уровень.
 *
 * ЭТАЛОН РАЗМЕТКИ (то, чего ждут и Bootstrap, и media/frontend.css + frontend.js):
 *
 *     <ul class="dropdown-menu">
 *       <li><a class="dropdown-item">Пункт</a></li>
 *       <li class="dropdown-submenu">
 *         <a class="dropdown-item dropdown-toggle" role="button" aria-expanded="false">Подраздел</a>
 *         <ul class="dropdown-menu">…</ul>
 *       </li>
 *     </ul>
 *
 * ⚠ ЧТО БЫЛО ДО 2026-09-04 И ПОЧЕМУ ЭТО ЛОМАЛОСЬ. Вложенный уровень оборачивался
 * в `<div class="dropdown" data-bs-toggle="dropdown">`, то есть становился
 * САМОСТОЯТЕЛЬНЫМ бутстраповским дропдауном внутри чужого меню. Последствия
 * (найдены по симптомам на живом меню темы berdrama):
 *
 *  1. `<div>` прямым потомком `<ul>` — невалидная разметка;
 *  2. правила наведения тем пишутся через `li` (`li:hover > .submenu`) и с обёрткой
 *     `div` не совпадали ни разу: верхний уровень раскрывался наведением,
 *     а вложенные — только кликом;
 *  3. Bootstrap вешал на вложенный уровень свой `.show` и никогда о нём не забывал,
 *     а закрытием верхнего уровня занималась тема — убрав курсор, пользователь
 *     получал закрытое дерево с «запомненными» раскрытыми ветками, и они
 *     показывались снова при следующем наведении;
 *  4. третий уровень уезжал за край экрана.
 *
 * Теперь вложенный уровень — обычный `<li>`, а раскрывает его собственный скрипт
 * виджета (media/frontend.js), потому что Bootstrap 5 многоуровневые меню
 * не поддерживает намеренно. Bootstrap на этих уровнях не участвует вовсе,
 * поэтому `data-bs-toggle` тут НЕТ: с ним Bootstrap считал бы подменю отдельным
 * дропдауном и закрывал бы родителя при каждом клике.
 *
 * ⚠ Внутри выпадашки пункт — это `.dropdown-item`, а не `.nav-link`: только у него
 * есть готовые отступы, ховер и вид активного пункта. Тема, которой нужен другой
 * класс, наследует этот виджет и указывает себя в `FrontendNav::$dropdownClass`.
 */
class FrontendDropdown extends Dropdown
{
    public function init(): void
    {
        Html::addCssClass($this->options, ['widget' => 'dropdown-menu']);
        parent::init();
    }

    /**
     * Рендеринг пунктов одного уровня.
     *
     * @param array $items пункты уровня
     * @param array $options HTML-атрибуты контейнера `<ul>`
     * @return string
     * @throws InvalidConfigException если у пункта нет 'label'
     * @throws Throwable
     */
    protected function renderItems($items, $options = []): string
    {
        $lines = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                // Разделитель у Bootstrap 5 — это `<hr>` внутри `<li>`, а не голый `<hr>`:
                // прямым потомком `<ul>` может быть только `<li>`.
                $lines[] = ($item === '-')
                    ? Html::tag('li', Html::tag('hr', '', ['class' => 'dropdown-divider']))
                    : $item;
                continue;
            }
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            if (!isset($item['label'])) {
                throw new InvalidConfigException("The 'label' option is required.");
            }
            $encodeLabel = $item['encode'] ?? $this->encodeLabels;
            $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
            // `name_addon` необязателен: обращение по ключу напрямую давало бы
            // E_WARNING на каждом пункте без него.
            $nameAddon = $item['name_addon'] ?? '';
            $label .= $encodeLabel ? Html::encode($nameAddon) : $nameAddon;

            $itemOptions = ArrayHelper::getValue($item, 'options', []);
            $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);
            // 'active' — подсвечена вся активная ветка (пункт или кто-то внутри него),
            // 'activeSelf' — пункт и есть текущая страница. Разметку различает только
            // `aria-current`: текущая страница одна, а подсвеченных пунктов в ветке
            // столько, какова её глубина. Оба флага ставит FrontendNav::isChildActive().
            $active = ArrayHelper::getValue($item, 'active', false);
            $activeSelf = ArrayHelper::getValue($item, 'activeSelf', false);
            $disabled = ArrayHelper::getValue($item, 'disabled', false);

            Html::addCssClass($linkOptions, ['widget' => 'dropdown-item']);

            if ($disabled) {
                ArrayHelper::setValue($linkOptions, 'tabindex', '-1');
                ArrayHelper::setValue($linkOptions, 'aria-disabled', 'true');
                Html::addCssClass($linkOptions, ['disable' => 'disabled']);
            } elseif ($active) {
                Html::addCssClass($linkOptions, ['activate' => 'active']);
                if ($activeSelf) {
                    ArrayHelper::setValue($linkOptions, 'aria-current', 'page');
                }
            }

            $url = $item['url'] ?? null;

            if (empty($item['items'])) {
                if ($url === null) {
                    // Пункт без адреса — заголовок группы, а не ссылка.
                    $content = Html::tag('li', Html::tag('h6', $label, ['class' => 'dropdown-header']));
                } else {
                    $content = Html::tag('li', Html::a($label, $url, $linkOptions), $itemOptions);
                }
                $lines[] = $content;
            } else {
                $submenuOptions = $this->submenuOptions;
                if (isset($item['submenuOptions'])) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $submenuOptions = array_merge($submenuOptions, $item['submenuOptions']);
                }
                Html::addCssClass($submenuOptions, ['widget' => 'dropdown-menu']);
                Html::addCssClass($linkOptions, ['toggle' => 'dropdown-toggle']);
                Html::addCssClass($itemOptions, ['widget' => 'dropdown-submenu']);

                // `role="button"` честен: клик по такому пункту перехватывает скрипт
                // виджета и раскрывает уровень, по адресу он не ведёт.
                // Состоянием `aria-expanded` дальше управляет тот же скрипт.
                $linkOptions['role'] = 'button';
                $linkOptions['aria-expanded'] = 'false';

                $lines[] = Html::tag(
                    'li',
                    Html::a($label, $url, $linkOptions) . static::widget([
                        'items' => $item['items'],
                        'options' => $submenuOptions,
                        'submenuOptions' => $submenuOptions,
                        'encodeLabels' => $this->encodeLabels,
                    ]),
                    $itemOptions
                );
            }
        }

        return Html::tag('ul', implode("\n", $lines), $options);
    }
}
