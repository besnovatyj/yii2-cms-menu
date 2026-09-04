/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/*
 * ВЛОЖЕННЫЕ УРОВНИ ВЫПАДАЮЩЕГО МЕНЮ.
 *
 * Bootstrap 5 умеет ровно один уровень, дальше — наша забота. Разметку уровня
 * см. в FrontendDropdown: <li class="dropdown-submenu"> + .dropdown-toggle + .dropdown-menu.
 *
 * ПОЧЕМУ КЛИК, А НЕ НАВЕДЕНИЕ. Первый уровень Bootstrap раскрывает по клику —
 * значит и вложенные должны открываться так же, иначе меню ведёт себя по-разному
 * на соседних уровнях. Клик к тому же одинаково работает на мыши, тач-экране
 * и с клавиатуры (Enter на ссылке — это click), а `:hover` на тач-экране
 * не существует. Теме, которой нужно наведение, хватит одного CSS-правила
 * (`.dropdown-submenu:hover > .dropdown-menu { display: block }`) — скрипт ему
 * не мешает, он трогает только класс `.show`.
 *
 * ПОЧЕМУ СЛУШАТЕЛЬ НА ФАЗЕ ПЕРЕХВАТА (третий аргумент `true`). Bootstrap слушает
 * клики делегированно на document и закрывает открытые выпадашки. Наш обработчик
 * на том же document в фазе всплытия мог бы оказаться и до, и после чужого —
 * порядок зависит от того, кто раньше подписался. В фазе перехвата мы гарантированно
 * первые, и `stopPropagation()` не даёт Bootstrap увидеть событие: родительское меню
 * остаётся открытым. Всё остальное (клики мимо подменю) идёт своим чередом.
 *
 * Скрипт самодостаточен: если Bootstrap на странице нет, вложенные уровни
 * всё равно раскрываются, просто некому будет прислать событие 'hidden.bs.dropdown'.
 */
(function () {
    'use strict';

    var TOGGLE_SELECTOR = '.dropdown-submenu > .dropdown-toggle';

    /**
     * Свернуть уровень и вернуть его переключателю честное aria-expanded.
     * @param {Element} menu
     */
    function closeMenu(menu) {
        menu.classList.remove('show');
        var toggle = menu.previousElementSibling;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Свернуть все вложенные уровни внутри узла — вместе с их собственными ветками.
     * @param {Element|Document} root
     */
    function closeInside(root) {
        var opened = root.querySelectorAll('.dropdown-submenu > .dropdown-menu.show');
        Array.prototype.forEach.call(opened, closeMenu);
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || typeof target.closest !== 'function') {
            return;
        }

        var toggle = target.closest(TOGGLE_SELECTOR);
        if (!toggle) {
            return;
        }

        var menu = toggle.nextElementSibling;
        if (!menu || !menu.classList.contains('dropdown-menu')) {
            return;
        }

        // По адресу такой пункт не ведёт: он раскрывает уровень (см. role="button"
        // в FrontendDropdown). Всплытие гасим, чтобы Bootstrap не закрыл родителя.
        event.preventDefault();
        event.stopPropagation();

        var wasOpen = menu.classList.contains('show');

        // Соседние ветки ТОГО ЖЕ уровня закрываем: открытым остаётся один путь вглубь,
        // иначе уровни наползают друг на друга — они позиционированы от одного края.
        var level = toggle.closest('.dropdown-menu');
        if (level) {
            closeInside(level);
        }

        if (!wasOpen) {
            menu.classList.add('show');
            toggle.setAttribute('aria-expanded', 'true');
        }
    }, true);

    /*
     * Родительская выпадашка закрылась (клик мимо, Esc, выбор пункта) — сворачиваем
     * всё, что было раскрыто внутри неё. Без этого меню «помнит» раскрытые ветки
     * и показывает их при следующем открытии.
     *
     * Событие Bootstrap шлёт на переключателе верхнего уровня, а он лежит рядом
     * со своей выпадашкой в общем <li> — его и берём за область очистки.
     */
    document.addEventListener('hidden.bs.dropdown', function (event) {
        var host = event.target && event.target.parentElement;
        closeInside(host || document);
    });
})();
