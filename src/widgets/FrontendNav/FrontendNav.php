<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\widgets\FrontendNav;

use Besnovatyj\Menu\repositories\MenuRepository;
use Exception;
use Throwable;
use Yii;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

/**
 * Меню сайта — БАЗОВЫЙ виджет модуля.
 *
 * Забирает дерево из БД по slug корневого узла и рендерит его СТАНДАРТНОЙ разметкой
 * Bootstrap 5, без единого класса, придуманного конкретной темой:
 *
 *     <ul class="navbar-nav">
 *       <li class="nav-item"><a class="nav-link">Пункт</a></li>
 *       <li class="nav-item dropdown">
 *         <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Раздел</a>
 *         <ul class="dropdown-menu">
 *           <li><a class="dropdown-item">Пункт</a></li>
 *           <li class="dropdown-submenu">
 *             <a class="dropdown-item dropdown-toggle">Подраздел</a>
 *             <ul class="dropdown-menu">…</ul>   ← и так на любую глубину
 *           </li>
 *         </ul>
 *       </li>
 *     </ul>
 *
 * Смысл такой разметки: виджет должен работать «из коробки» в ЛЮБОЙ теме на голом
 * Bootstrap 5 — это рабочий пример доступного функционала, а не полуфабрикат под
 * одну тему. Всё, что Bootstrap умеет сам (раскладка, цвета, состояние `.active`,
 * раскрытие первого уровня по клику), берётся у него; собственного CSS у виджета
 * ровно столько, сколько нужно вложенным уровням — их Bootstrap 5 не поддерживает
 * намеренно (см. заметку «Multilevel dropdowns beyond Level 1 are not supported»
 * в {@see Nav}). Эти крохи лежат в {@see Assets} (media/frontend.css + frontend.js).
 *
 * ЧТО ЗДЕСЬ ЕСТЬ, ЧЕГО НЕТ У РОДИТЕЛЯ:
 *  - items берутся из БД по {@see $slug}, а не задаются вручную;
 *  - активность считается ещё и по `active_string` пункта, а при пустом поле —
 *    по его же URL (так обещает подсказка в форме пункта), см. {@see isItemActive};
 *  - активность ПОДНИМАЕТСЯ до самого верха дерева с любой глубины, см. {@see isChildActive};
 *  - вложенные уровни рендерятся валидной разметкой списка, см. {@see FrontendDropdown}.
 *
 * КАК ПЕРЕОПРЕДЕЛИТЬ ПОД ТЕМУ. Тема наследует этот класс, переопределяет
 * {@see renderItem} (презентация верхнего уровня) и ОБЯЗАТЕЛЬНО указывает свой
 * {@see $dropdownClass} — иначе вложенные уровни продолжит рендерить виджет пакета,
 * и половина правок «в теме» окажется мёртвым кодом. Логика (выборка, дерево,
 * активность) при этом наследуется как есть и дублировать её в теме не нужно.
 * Ассет-бандл темы, если он рисует меню целиком, снимается так:
 * `unset($this->view->assetBundles[Assets::class])` после `parent::init()`.
 */
class FrontendNav extends Nav
{
    // TODO ----------------------------------------------------
    // TODO настроить кеширование меню для фронтэнда, а то каждый раз в базу лезет (бэкэнд при редактировании должен сбрасывать кеш)
    // TODO ----------------------------------------------------

    public $dropdownClass = FrontendDropdown::class;
    public string $slug = 'main'; // Slug меню по умолчанию
    private MenuRepository $repository;

    public function init(): void
    {
        $this->repository = new MenuRepository();
        Assets::register($this->view);
        parent::init();
        $this->items = $this->getMenuItems();
        // Html::addCssClass($this->options, ['widget' => 'navbar-nav']);
        Html::removeCssClass($this->options, ['widget' => 'nav']);
    }

    /**
     * TODO см. рекурсивный хелерл от Грока: `\Besnovatyj\Helpers\TreeBuilder::build($nodes);`
     * Получение пунктов меню из репозитория.
     * Использует getSubtreeBySlug для извлечения активных дочерних узлов корневого узла с заданным slug.
     * Корневой узел не включается в меню, отображаются только его дочерние узлы.
     * @return array
     */
    private function getMenuItems(): array
    {
        $cacheKey = 'menu_items_' . $this->slug;
//        return Yii::$app->cache->getOrSet($cacheKey, function () {
        $menuNodes = $this->repository->getSubtreeBySlug($this->slug);
        if (empty($menuNodes)) {
            Yii::warning("Нет активных узлов для меню со slug '{$this->slug}'.", __METHOD__);
            return [];
        }

        $items = [];
        $stack = []; // Стек для отслеживания родительских узлов

        foreach ($menuNodes as $node) {
            // Удаляем из стека узлы, которые не являются родителями текущего узла
            while ($stack && ($node->lft > end($stack)['node']->rgt || $node->depth <= end($stack)['node']->depth)) {
                array_pop($stack);
            }

            // Определяем родительский массив items
            $parentItems = &$items;
            foreach ($stack as $parent) {
                $parentItems = &$parentItems[$parent['index']]['items'];
            }

            // Создаем элемент меню
            $item = [
                'label' => $node->name,
                'name_addon' => $node->name_addon,
                'encode' => $node->encode ?? true,
                'url' => $node->url ?: '#',
                'active_string' => $node->active_string,
                'items' => [],
            ];

            // Добавляем элемент в родительский массив
            $index = count($parentItems);
            $parentItems[$index] = $item;

            // Добавляем узел в стек
            $stack[] = [
                'node' => $node,
                'index' => $index,
            ];
        }

        Yii::info("Построено меню со slug: {$this->slug}, узлов: " . count($menuNodes) . ", структура: " . json_encode($items, JSON_UNESCAPED_UNICODE), __METHOD__);
        return $items;
//        }, 3600); // Кэш на 1 час
    }

    /**
     * Проверяет, есть ли активные дочерние элементы, и возвращает массив элементов.
     *
     * ⚠ ПОРЯДОК ДЕЙСТВИЙ ЗДЕСЬ — ЭТО И ЕСТЬ ЛОГИКА, а не оформление. До 2026-09-04
     * флаг родителя выставлялся ДО спуска в потомков:
     *
     *     $childActive = $this->isItemActive($item);
     *     if ($childActive) { $active = true; }                              // ← проверка тут
     *     $item['items'] = $this->isChildActive($childItems, $childActive);  // ← а тут $childActive мог стать true
     *
     * то есть активность поднималась ровно на один уровень. На меню глубже двух
     * уровней это выглядело так: открытый пункт ТРЕТЬЕГО уровня подсвечивал свой
     * второй уровень, а до верхнего не доходил — раздел в шапке оставался неактивным,
     * хотя пользователь стоял внутри него. Теперь рекурсия идёт ПЕРВОЙ, и флаг
     * поднимается с любой глубины.
     *
     * Два флага на пункт, и они не синонимы:
     *  - `active`     — пункт подсвечивается: либо он сам текущий, либо текущий кто-то
     *                   ВНУТРИ него (активная ветка целиком, от корня до листа);
     *  - `activeSelf` — пункт и есть текущая страница. Только он получает `aria-current`:
     *                   вешать его на всю ветку — врать скринридеру, текущая страница одна.
     *
     * От родителя ({@see Nav::isChildActive}) поведение отличается намеренно: там
     * подъём к родителю включается флагом `activateParents` и по умолчанию выключен,
     * здесь он всегда включён. Меню сайта — это дерево разделов, и подсветка раздела,
     * внутри которого находится посетитель, тут не опция, а нормальное поведение.
     *
     * @param array $items Дочерние элементы
     * @param bool $active Флаг активности родителя (по ссылке: поднимается наверх)
     * @return array
     * @throws Exception
     */
    protected function isChildActive(array $items, bool &$active): array
    {
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                // Строковые элементы (разделитель '-', готовый HTML) активности не имеют,
                // но и терять их нельзя — отдаём как есть.
                $result[] = $item;
                continue;
            }

            $selfActive = $this->isItemActive($item);
            $branchActive = false;

            $childItems = ArrayHelper::getValue($item, 'items', []);
            $item['items'] = is_array($childItems)
                ? $this->isChildActive($childItems, $branchActive)
                : $childItems;

            if ($selfActive) {
                $item['activeSelf'] = true;
            }
            if ($selfActive || $branchActive) {
                $item['active'] = true;
                $active = true;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Рендеринг пункта меню ВЕРХНЕГО уровня.
     *
     * @param string|array $item
     * @return string
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function renderItem($item): string
    {
        if (is_string($item)) {
            return $item;
        }
        if (!isset($item['label'])) {
            throw new InvalidConfigException("Опция 'label' обязательна.");
        }
        $encodeLabel = $item['encode'] ?? $this->encodeLabels;
        $label = $encodeLabel ? Html::encode($item['label']) : $item['label'];
        // `name_addon` необязателен: обращение по ключу напрямую давало бы
        // E_WARNING на каждом пункте без него.
        $nameAddon = $item['name_addon'] ?? '';
        $label .= $encodeLabel ? Html::encode($nameAddon) : $nameAddon;

        $options = ArrayHelper::getValue($item, 'options', []);
        $items = ArrayHelper::getValue($item, 'items', []);
        $url = ArrayHelper::getValue($item, 'url', '#');
        $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);

        // Свой ли это адрес (`aria-current`) — считаем ДО спуска в потомков:
        // ниже $active станет true и от того, что активен кто-то внутри.
        $selfActive = $this->isItemActive($item);
        $active = $selfActive;

        if (empty($items) || !is_array($items)) {
            $items = ''; // Гарантируем, что $items - строка
            Html::addCssClass($options, ['widget' => 'nav-item']);
            Html::addCssClass($linkOptions, ['widget' => 'nav-link']);
        } else {
            // Первый уровень раскрывает сам Bootstrap по клику — свой скрипт тут не нужен.
            // Клик по такому пункту НЕ ведёт по его адресу: Bootstrap перехватывает событие,
            // поэтому пункт ведёт себя как кнопка, и `role="button"` здесь не ложь.
            // Тема, которая раскрывает первый уровень наведением, переопределяет renderItem
            // и решает судьбу этого атрибута сама.
            $linkOptions['data-bs-toggle'] = 'dropdown';
            $linkOptions['role'] = 'button';
            $linkOptions['aria-expanded'] = 'false';
            Html::addCssClass($options, ['widget' => 'nav-item dropdown']);
            Html::addCssClass($linkOptions, ['widget' => 'nav-link dropdown-toggle']);
            // Каретку рисует Bootstrap (`.dropdown-toggle::after`) — своей иконки не вставляем,
            // иначе тема получит две стрелки и будет гасить чужую.

            $items = $this->isChildActive($items, $active);
            $dropdownHtml = $this->renderDropdown($items, $item);
            $items = is_string($dropdownHtml) ? $dropdownHtml : ''; // Гарантируем, что $items - строка
        }

        if ($this->activateItems && $active) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);
        }
        if ($this->activateItems && $selfActive) {
            $linkOptions['aria-current'] = 'page';
        }

        return Html::tag('li', Html::a($label, $url, $linkOptions) . $items, $options);
    }

    /**
     * Проверка активности пункта меню.
     *
     * Сверяемая строка берётся из `active_string` пункта, а если поле пустое —
     * из его же `url`. Так работает подсказка в форме пункта («Часть URL, при
     * совпадении с которой пункт подсвечивается активным. По умолчанию — сам URL»):
     * до 2026-09-04 обещанного умолчания в коде не было, и у пунктов с пустым
     * `active_string` подсветка не включалась НИКОГДА — родительская проверка
     * работает только с маршрутом-массивом, а из БД url приходит строкой.
     *
     * Сверяем с двумя строками сразу, потому что в поле может лежать и то и другое:
     *  - маршрут с параметрами (`page/frontend/page/view?slug=about`) — так пункт
     *    привязывают к действию контроллера;
     *  - путь запроса (`/about`) — так его заполняет форма пункта, подставляя URL.
     * Совпадение по любой из них считается попаданием.
     *
     * @param array $item
     * @param bool $fullMatch - Если true, сравнивается на идентичность, если false, то содержится ли.
     * @param bool $caseInsensitive - Если true, то не учитывать регистр
     * @return bool
     * @throws Exception
     */
    protected function isItemActive(array $item, bool $fullMatch = false, bool $caseInsensitive = true): bool
    {
        if (!$this->activateItems) {
            return false;
        }

        $needle = trim((string)($item['active_string'] ?? ''), '/');
        $itemUrl = $item['url'] ?? null;

        // '#' — пункт-контейнер, своей страницы у него нет: подсветку он получает
        // только от активного потомка (см. isChildActive), сам активным не бывает.
        if ($needle === '' && is_string($itemUrl) && $itemUrl !== '#') {
            $needle = trim($itemUrl, '/');
            // Отдельный случай — ссылка на главную ('/'). После trim от неё ничего
            // не остаётся, а пустая строка содержится в любом адресе, то есть пункт
            // «подсветился» бы сразу на всех страницах. Поэтому главная сверяется
            // на точное совпадение с пустым путём запроса.
            if ($needle === '') {
                return Yii::$app->request->getPathInfo() === '';
            }
        }

        if ($needle !== '') {
            // Маршрут и параметры берём у родителя (Nav::$route/$params): по умолчанию
            // это текущий запрос, но их можно задать снаружи — например, чтобы отрисовать
            // меню от имени другой страницы.
            $params = $this->params ?? [];
            $haystacks = [
                trim((string)$this->route . (empty($params) ? '' : '?' . http_build_query($params)), '/'),
                trim(Yii::$app->request->getPathInfo(), '/'),
            ];

            if ($caseInsensitive) {
                $needle = mb_strtolower($needle);
                $haystacks = array_map('mb_strtolower', $haystacks);
            }

            foreach ($haystacks as $haystack) {
                if ($fullMatch ? $needle === $haystack : str_contains($haystack, $needle)) {
                    return true;
                }
            }
        }

        return parent::isItemActive($item);
    }
}
