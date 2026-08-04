<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Menu\controllers\backend;

use Besnovatyj\Menu\forms\backend\MenuItemForm;
use Besnovatyj\Menu\readModels\MenuReadRepository;
use Besnovatyj\Menu\services\MenuTargetRegistry;
use Besnovatyj\TreeManager\Manager\TreeManager;
use Throwable;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Страница добавления пункта меню на основе целей, объявленных другими модулями через
 * {@see \Besnovatyj\Contracts\menu\MenuTargetProvider}.
 *
 * Каскад «модуль → цель → кандидат» собирается {@see MenuTargetRegistry}; выбранный кандидат
 * предзаполняет имя/URL/slug, после чего пункт создаётся через `menu.tree.manager` — тот же путь,
 * что и у виджета {@see \Besnovatyj\Menu\widgets\add\AddItemWidget} и дерева меню.
 */
class TargetController extends Controller
{
    private TreeManager $treeManager;

    public function __construct(
        $id,
        $module,
        private readonly MenuTargetRegistry $registry,
        private readonly MenuReadRepository $menuRead,
        $config = [],
    ) {
        /** @var TreeManager $treeManager */
        $treeManager = Yii::$container->get('menu.tree.manager');
        $this->treeManager = $treeManager;
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['create' => ['POST']],
            ],
        ]);
    }

    public function actionIndex(): string
    {
        return $this->renderForm(new MenuItemForm());
    }

    public function actionCreate(): Response|string
    {
        $form = new MenuItemForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $this->treeManager->createNode($form);
                Yii::$app->session->setFlash('success', 'Пункт меню создан.');
                return $this->redirect(['/Menu/backend/widget/index']);
            } catch (Throwable $e) {
                Yii::$app->errorHandler->logException($e);
                Yii::$app->session->addFlash('error', YII_DEBUG ? $e->getMessage() : 'Ошибка при создании пункта меню.');
            }
        }

        if ($form->hasErrors()) {
            Yii::$app->session->addFlash('error', $form->getErrorSummary(true));
        }

        return $this->renderForm($form);
    }

    private function renderForm(MenuItemForm $form): string
    {
        return $this->render('index', [
            'model' => $form,
            'catalog' => $this->registry->catalog(),
            'parents' => $this->menuRead->selectOptions(),
        ]);
    }
}
