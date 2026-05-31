<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\widgets\FrontendNav;

use yii\bootstrap5\Html;
use yii\bootstrap5\Dropdown;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

class FrontendDropdown extends Dropdown
{
    public function init(): void
    {
        Html::addCssClass($this->options, ['widget' => 'dropdown-menu animate fade-down']);
        parent::init();
    }

    protected function renderItems($items, $options = []): string
    {
        $lines = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $lines[] = ($item === '-') ? Html::tag('hr', '', ['class' => 'dropdown-divider']) : $item;
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
            $nameAddon = $encodeLabel && $item['name_addon'] ? Html::encode($item['name_addon']) : $item['name_addon'];
            $label = '<span class="text">' . $label . '</span>' . ($nameAddon ?: '');

            $itemOptions = ArrayHelper::getValue($item, 'options', []);
            $linkOptions = ArrayHelper::getValue($item, 'linkOptions', []);
            $active = ArrayHelper::getValue($item, 'active', false);

            Html::addCssClass($linkOptions, ['widget' => 'nav-link']);

            if ($active) {
                ArrayHelper::setValue($linkOptions, 'aria-current', 'true');
                Html::addCssClass($linkOptions, ['activate' => 'active']);
            }

            $url = $item['url'] ?? null;

            if (empty($item['items'])) {
                if ($url === null) {
                    $content = Html::tag('h6', $label, ['class' => 'dropdown-header']);
                } else {
                    $content = Html::tag('li', Html::a($label, $url, $linkOptions), ['class' => 'nav-item']);
                }
                $lines[] = $content;
            } else {
                $submenuOptions = $this->submenuOptions;
                if (isset($item['submenuOptions'])) {
                    $submenuOptions = array_merge($submenuOptions, $item['submenuOptions']);
                }
                Html::addCssClass($submenuOptions, ['widget' => 'dropdown-submenu dropdown-menu']);
                Html::addCssClass($linkOptions, ['toggle' => 'dropdown-toggle']);

                $lines[] = Html::beginTag('div', array_merge(['class' => ['dropdown'], 'aria-expanded' => 'false'], $itemOptions));
                $lines[] = Html::a($label, $url, array_merge([
                    'data-bs-toggle' => 'dropdown',
                    'aria-expanded' => 'false',
                    'role' => 'button',
                ], $linkOptions));
                $lines[] = static::widget([
                    'items' => $item['items'],
                    'options' => $submenuOptions,
                    'submenuOptions' => $submenuOptions,
                    'encodeLabels' => $this->encodeLabels,
                ]);
                $lines[] = Html::endTag('div');
            }
        }

        return Html::tag('ul', implode("\n", $lines), $options);
    }
}
