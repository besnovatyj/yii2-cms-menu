<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Menu\services;

use Besnovatyj\Menu\repositories\MenuRepository;

class MenuService
{
    protected MenuRepository $menuRepo;

    public function __construct()
    {
        $this->menuRepo = new MenuRepository();
    }

}
