<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\controllers\rest;

use Besnovatyj\Menu\providers\MapDataProvider;
use Besnovatyj\Menu\entities\Menu;
use Besnovatyj\Menu\readModels\MenuReadRepository;
use yii\data\DataProviderInterface;
use yii\helpers\Url;
use yii\rest\Controller;

class MenuController extends Controller
{
    private MenuReadRepository $menus;

    public function __construct(
        $id,
        $module,
        MenuReadRepository $menus,
        $config = []
    )
    {
        parent::__construct($id, $module, $config);
        $this->menus = $menus;
    }

    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
        ];
    }

    public function actionIndex(): DataProviderInterface
    {
        $dataProvider = $this->menus->getAll();
        return new MapDataProvider($dataProvider, [$this, 'serializeListItem']);
    }

    public function serializeListItem(Menu $menuRest): array
    {
        return [
            'id' => $menuRest->id,
            'tree' => $menuRest->tree,
            'left' => $menuRest->lft,
            'right' => $menuRest->rgt,
            'depth' => $menuRest->depth,
            'name' => $menuRest->name,
            'name_addon' => $menuRest->name_addon,
            'encode' => $menuRest->encode,
            'url' => $menuRest->url,
            'active_string' => $menuRest->active_string,
            'status' => $menuRest->status,
            'slug' => $menuRest->slug,
            '_links' => [
                'self' => ['href' => Url::to(['todo', 'id' => $menuRest->url], true)],
            ],
        ];
    }


}

