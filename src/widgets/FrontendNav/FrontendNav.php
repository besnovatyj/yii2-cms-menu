<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\widgets\FrontendNav;

use Besnovatyj\Menu\repositories\MenuRepository;
use Exception;
use Besnovatyj\Menu\widgets\FrontendNav\Assets;
use Besnovatyj\Menu\widgets\FrontendNav\FrontendDropdown;
use Throwable;
use Yii;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

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
     * @param array $items Дочерние элементы
     * @param bool $active Флаг активности родителя
     * @return array
     * @throws Exception
     */
    protected function isChildActive(array $items, bool &$active): array
    {
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $childActive = $this->isItemActive($item);
            if ($childActive) {
                $active = true;
            }
            $childItems = ArrayHelper::getValue($item, 'items', []);
            $item['items'] = $this->isChildActive($childItems, $childActive);
            if ($childActive) {
                $item['active'] = true;
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Рендеринг пункта меню.
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
        $nameAddon = $encodeLabel && $item['name_addon'] ? Html::encode($item['name_addon']) : $item['name_addon'];
        $options = ArrayHelper::getValue($item, 'options', []);
        $items = ArrayHelper::getValue($item, 'items', []);
        $url = ArrayHelper::getValue($item, 'url', '#');
        $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);
        $active = $this->isItemActive($item);

        if (empty($items) || !is_array($items)) {
            Html::addCssClass($options, ['widget' => 'nav-item']);
            Html::addCssClass($linkOptions, ['widget' => 'nav-link']);
            $label = '<span class="text">' . $label . '</span>' . ($nameAddon ?: '');
            $items = ''; // Гарантируем, что $items - строка
        } else {
            $linkOptions['data-bs-toggle'] = 'dropdown';
            $linkOptions['role'] = 'button';
            $linkOptions['aria-expanded'] = 'false';
            Html::addCssClass($options, ['widget' => 'nav-item dropdown hover']);
            Html::addCssClass($linkOptions, ['widget' => 'nav-link dropdown-toggle has-icon']);
            $label = '<span class="text">' . $label . '</span>' . ($nameAddon ?: '') . '
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" class="image-icon dropdown-icon">
<title></title>
<polyline points="112 184 256 328 400 184" style="fill:none;stroke:inherit;stroke-linecap:round;stroke-linejoin:round;stroke-width:48px"></polyline>
</svg>';
            $items = $this->isChildActive($items, $active);
            $dropdownHtml = $this->renderDropdown($items, $item);
            $items = is_string($dropdownHtml) ? $dropdownHtml : ''; // Гарантируем, что $items - строка
        }

        if ($this->activateItems && $active) {
            Html::addCssClass($linkOptions, ['activate' => 'active']);
        }

        return Html::tag('li', Html::a($label, $url, $linkOptions) . $items, $options);
    }

    /**
     * Проверка активности пункта меню.
     * @param array $item
     * @param bool $fullMatch - Если true, сравнивается на идентичность, если false, то содержится ли.
     * @param bool $caseInsensitive - Если true, то не учитывать регистр
     * @return bool
     * @throws Exception
     */
    protected function isItemActive(array $item, bool $fullMatch = false, bool $caseInsensitive = true): bool
    {
        if (isset($item['active_string']) && is_string($item['active_string'])) {
            $route = Yii::$app->controller->getRoute();
            $params = Yii::$app->request->getQueryParams();
            $url = trim($item['active_string'], '/');
            $currentUrl = trim($route . (empty($params) ? '' : '?' . http_build_query($params)), '/');
            if ($caseInsensitive) {
                $currentUrl = strtolower($currentUrl);
                $url = strtolower($url);
            }
            if ($fullMatch) { // Вариант полного совпадения
                if ($url === $currentUrl) {
                    return true;
                }
            } else { // Проверяем, содержится ли active_string в текущем URL
                if ($url && str_contains($currentUrl, $url)) {
                    return true;
                }
            }
        }
        return parent::isItemActive($item);
    }
}
