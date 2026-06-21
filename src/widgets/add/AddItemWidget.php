<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Menu\widgets\add;

use Besnovatyj\Alert\AlertAsset;
use Besnovatyj\Menu\forms\backend\MenuItemForm;
use Besnovatyj\Menu\widgets\add\assets\Assets;
use DomainException;
use Yii;
use yii\bootstrap5\Widget;
use yii\helpers\Inflector;

/**
 * Виджет для добавления ссылки на страницу фронтенда в качестве пункта меню.
 *
 * Подключается на любой странице бэкенда. Рендерит кнопку, открывающую
 * Bootstrap-модалку с предзаполненной формой {@see MenuItemForm}.
 * Отправка формы обрабатывается TypeScript через Fetch API.
 *
 * Пример использования:
 * ```php
 * echo \Besnovatyj\Menu\widgets\add\AddItemWidget::widget([
 *     'endpoint' => \yii\helpers\Url::to(['/Menu/backend/widget/create']),
 *     'link'     => $frontendUrl,
 *     'name'     => $page->title,
 * ]);
 * ```
 */
class AddItemWidget extends Widget
{
    /** @var string URL страницы фронтенда, которая станет ссылкой пункта меню */
    public string $link = '';

    /** @var string Отображаемое название пункта меню */
    public string $name = '';

    /** @var string URL эндпоинта TreeController::actionCreate() */
    public string $endpoint = '';

    /** @var int|null ID родительского узла дерева (null — создаётся корневым) */
    public ?int $parentId = null;

    public function init(): void
    {
        parent::init();

        if (empty($this->link) || empty($this->name) || empty($this->endpoint)) {
            throw new DomainException("Параметры 'link', 'endpoint' и 'name' обязательны.");
        }

        AlertAsset::register($this->view);
        Assets::register($this->view);
    }

    public function run(): string
    {
        $form = new MenuItemForm(null, $this->parentId);

        $form->url           = $this->link;
        $form->active_string = $this->link;
        $form->name          = $this->name;
        $form->slug          = Inflector::slug($this->name);
        $form->status        = 1;
        $form->encode        = 1;

        return $this->render('modal', [
            'model'   => $form,
            'modalId' => 'add-menu-item-modal-' . $this->id,
            'config'  => [
                'endpoint'    => $this->endpoint,
                'csrfHeaders' => [
                    'X-CSRF-Token'           => Yii::$app->request->getCsrfToken(),
                    'X-Requested-With'       => 'XMLHttpRequest',
                ],
            ],
        ]);
    }
}
