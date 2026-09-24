<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\widgets\FrontendNav;

use yii\bootstrap5\Html;

/**
 * Оформительские поля пункта меню → HTML-атрибуты: `new_tab` и `css_class`.
 *
 * ЗАЧЕМ ОТДЕЛЬНЫЙ КЛАСС, А НЕ МЕТОДЫ В {@see FrontendNav}. Пункт меню рисуют пять разных
 * классов, и ни одной общей базы у них нет: верхний уровень — наследник `Nav`, выпадающие
 * уровни — наследники `Dropdown`, панель раздела и меню подвала в теме — обычные виджеты.
 * Значит наследованием одно место не получается, а размазать по пяти файлам одинаковые
 * четыре строки — это ровно тот случай, когда следующая правка (скажем, `rel`) найдётся
 * в трёх местах из пяти. Поэтому применение флагов живёт здесь, а каждый рендерер
 * зовёт его перед сборкой своего тега.
 *
 * ОБА ПОЛЯ НЕОБЯЗАТЕЛЬНЫ. Пункты приходят и из меню, собранного не из БД (например,
 * из массива в шаблоне), поэтому ключи читаются через `??` и отсутствие поля —
 * не ошибка, а норма.
 *
 * @see FrontendNav::getMenuItems() — там эти поля попадают в `items`.
 */
final class ItemAttributes
{
    /**
     * Атрибуты ссылки пункта: открытие в новой вкладке.
     *
     * `rel="noopener"` добавляется вместе с `target`, и это не формальность: без него
     * открытая страница получает доступ к `window.opener`. Современные браузеры так
     * не делают и сами, но ссылка живёт дольше, чем версия браузера у посетителя.
     * Свой `rel`, если он задан в `linkOptions` снаружи, не перетираем — там может
     * лежать `nofollow` партнёрской ссылки.
     *
     * Скринридеру о новой вкладке ничего не сообщается намеренно: приписка «(откроется
     * в новой вкладке)» в названии пункта меню читалась бы при каждом проходе по шапке.
     * Если понадобится — её место здесь, а не в пяти рендерерах.
     *
     * @param array<string, mixed> $item Пункт меню.
     * @param array<string, mixed> $linkOptions HTML-атрибуты `<a>`.
     * @return array<string, mixed> Атрибуты с учётом флага.
     */
    public static function applyToLink(array $item, array $linkOptions): array
    {
        if (empty($item['new_tab'])) {
            return $linkOptions;
        }

        $linkOptions['target'] = '_blank';
        if (!isset($linkOptions['rel'])) {
            $linkOptions['rel'] = 'noopener';
        }

        return $linkOptions;
    }

    /**
     * Атрибуты контейнера пункта (`<li>`): произвольные классы из админки.
     *
     * Ключ `custom` у {@see Html::addCssClass()} свой, отдельный от `widget`/`activate`
     * и прочих: так класс из админки не может вытеснить несущий класс разметки
     * (`nav-item`, `dropdown`) и наоборот.
     *
     * @param array<string, mixed> $item Пункт меню.
     * @param array<string, mixed> $options HTML-атрибуты `<li>`.
     * @return array<string, mixed> Атрибуты с учётом классов пункта.
     */
    public static function applyToContainer(array $item, array $options): array
    {
        $class = trim((string)($item['css_class'] ?? ''));
        if ($class === '') {
            return $options;
        }

        Html::addCssClass($options, ['custom' => $class]);

        return $options;
    }
}
