/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

/*
 * СКРИПТ ВИДЖЕТА МЕНЮ. Две задачи, обе — про то, чего Bootstrap 5 не делает сам:
 * вложенные уровни (их он не поддерживает) и удержание выпадашки в окне
 * (внутри навбара он этим не занимается). Ниже по порядку.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. ВЛОЖЕННЫЕ УРОВНИ ВЫПАДАЮЩЕГО МЕНЮ.
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
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * 2. ВЫПАДАШКА, УБЕГАЮЩАЯ ЗА КРАЙ ОКНА.
 *
 * ⚠ Внутри `.navbar` Bootstrap НЕ ДЕРЖИТ выпадашку в пределах экрана, и это его
 * штатное поведение, а не поломка вёрстки. В `Dropdown._getPopperConfig()` есть
 * ветка `if (this._inNavbar || display === 'static')`: она отключает у Popper
 * модификатор `applyStyles` и ставит на меню `data-bs-popper="static"`. То есть
 * в навбаре Popper фактически выключен — а вместе с ним и оба механизма, которые
 * обычно спасают от края экрана (`flip` и `preventOverflow`). Позиционирует меню
 * голый CSS: `.dropdown-menu[data-bs-popper] { top: 100%; left: 0 }`, то есть
 * всегда от ЛЕВОГО края своего пункта.
 *
 * Пока меню слева — это незаметно. Но `navbar-nav ms-auto` прижимает пункты
 * к правому краю, и список последнего пункта уезжает за окно целиком.
 * Лечится штатным бутстраповским классом `.dropdown-menu-end` (правило
 * `.dropdown-menu-end[data-bs-popper] { right: 0; left: auto }`) — им и лечим,
 * но не всем подряд, а только тем меню, которые реально не поместились:
 * замер возможен лишь после открытия, до него ширины у меню нет.
 *
 * Тема, которая знает про свой навбар заранее, может просто отдать пункту
 * `'dropdownOptions' => ['class' => 'dropdown-menu-end']` — скрипт это увидит
 * и мешать не станет: он снимает только то, что повесил сам (метка
 * `data-menu-autoaligned`).
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
        menu.classList.remove('dropdown-submenu-flip');
        var toggle = menu.previousElementSibling;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Уровень открыт — не вылез ли он за правый край окна. Если вылез, отражаем его
     * влево от родителя (CSS-класс). Замер возможен только после открытия: у закрытого
     * уровня `display: none`, и ширины у него нет.
     *
     * Ниже брейкпоинта раскрытия навбара уровни стоят в потоке и за край не выходят —
     * проверка там просто ничего не находит.
     *
     * @param {Element} menu
     */
    function flipIfClipped(menu) {
        var viewport = document.documentElement.clientWidth;
        if (menu.getBoundingClientRect().right <= viewport) {
            return;
        }

        menu.classList.add('dropdown-submenu-flip');

        // Слева места оказалось ещё меньше (узкое окно, глубокая ветка) — откатываемся:
        // уехавший вправо уровень хотя бы начинается на экране.
        if (menu.getBoundingClientRect().left < 0) {
            menu.classList.remove('dropdown-submenu-flip');
        }
    }

    /**
     * Выпадашка пункта, на котором сработало событие Bootstrap. В нашей разметке
     * меню — соседний элемент переключателя; запасной вариант нужен на случай,
     * если событие придёт на контейнере (у разных версий Bootstrap это менялось).
     *
     * @param {Element} toggle
     * @return {Element|null}
     */
    function menuOf(toggle) {
        if (!toggle || typeof toggle.querySelector !== 'function') {
            return null;
        }
        var next = toggle.nextElementSibling;
        if (next && next.classList.contains('dropdown-menu')) {
            return next;
        }

        return toggle.querySelector('.dropdown-menu');
    }

    /**
     * Выпадашка первого уровня не поместилась справа — прижимаем её правым краем
     * к своему пункту штатным классом Bootstrap. Метку ставим свою: снимать класс
     * при закрытии можно только у тех меню, которым мы его и повесили, — иначе
     * затрём выравнивание, заданное темой осознанно.
     *
     * @param {Element} menu
     */
    function alignIfClipped(menu) {
        var viewport = document.documentElement.clientWidth;
        if (menu.getBoundingClientRect().right <= viewport) {
            return;
        }

        menu.classList.add('dropdown-menu-end');
        menu.setAttribute('data-menu-autoaligned', '');

        // На узком экране меню бывает шире окна: тогда выравнивание по правому краю
        // просто перевешивает проблему на левый. В этом случае откатываемся —
        // пусть лучше не помещается справа, там хотя бы начало текста видно.
        if (menu.getBoundingClientRect().left < 0) {
            resetAlignment(menu);
        }
    }

    /**
     * Снять выравнивание, если его поставил скрипт (см. alignIfClipped).
     * @param {Element} menu
     */
    function resetAlignment(menu) {
        if (menu && menu.hasAttribute('data-menu-autoaligned')) {
            menu.classList.remove('dropdown-menu-end');
            menu.removeAttribute('data-menu-autoaligned');
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
            flipIfClipped(menu);
        }
    }, true);

    /*
     * Выпадашка первого уровня открылась — проверяем, поместилась ли она в окно.
     * Раньше открытия проверять нечего: у закрытого меню нет ни ширины, ни координат.
     */
    document.addEventListener('shown.bs.dropdown', function (event) {
        var menu = menuOf(event.target);
        if (menu) {
            alignIfClipped(menu);
        }
    });

    /*
     * Родительская выпадашка закрылась (клик мимо, Esc, выбор пункта) — сворачиваем
     * всё, что было раскрыто внутри неё, и возвращаем ей исходное выравнивание.
     * Без первого меню «помнит» раскрытые ветки и показывает их при следующем
     * открытии; без второго оно навсегда останется прижатым вправо, даже когда
     * окно снова станет широким.
     *
     * Событие Bootstrap шлёт на переключателе верхнего уровня, а он лежит рядом
     * со своей выпадашкой в общем <li> — его и берём за область очистки.
     */
    document.addEventListener('hidden.bs.dropdown', function (event) {
        var host = event.target && event.target.parentElement;
        closeInside(host || document);
        resetAlignment(menuOf(event.target));
    });
})();
