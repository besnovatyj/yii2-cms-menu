<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

declare(strict_types=1);

namespace Besnovatyj\Menu\services;

use Besnovatyj\Contracts\menu\MenuTargetProvider;
use Besnovatyj\Kernel\urlmanager\UrlManagerHelperTrait;
use Throwable;
use Yii;

/**
 * Находит модули, объявляющие цели для меню ({@see MenuTargetProvider}), и агрегирует их для админки.
 *
 * Discovery — через перебор зарегистрированных модулей приложения и проверку `instanceof` (та же
 * механика, что у {@see \Besnovatyj\RouteAlias\services\AliasTargetRegistry}). Работает только на
 * бэкенде (страница добавления пункта меню из модулей), не на горячем пути, поэтому инстанцирование
 * модулей здесь допустимо. Связанность нулевая: провайдеры не знают об этом модуле.
 *
 * Для каждого кандидата сразу строит готовый фронтовый URL через `frontendUrlManager` — так страница
 * получает всё необходимое одним массивом, а JS-каскад работает без обращений к серверу.
 */
final class MenuTargetRegistry
{
    use UrlManagerHelperTrait;

    /** @var array<string,MenuTargetProvider>|null */
    private ?array $providers = null;

    /**
     * Провайдеры целей, ключ — id модуля.
     *
     * @return array<string,MenuTargetProvider>
     */
    public function providers(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $providers = [];
        foreach (array_keys(Yii::$app->getModules()) as $id) {
            $module = Yii::$app->getModule((string)$id);
            if ($module instanceof MenuTargetProvider) {
                $providers[(string)$id] = $module;
            }
        }

        return $this->providers = $providers;
    }

    /**
     * Данные для каскада админки: модуль → цели → кандидаты (slug, подпись, готовый URL).
     *
     * @return array<string,array{
     *     label:string,
     *     targets:list<array{route:string,label:string,slugParam:string}>,
     *     candidates:array<string,list<array{slug:string,label:string,url:string}>>
     * }>
     */
    public function catalog(): array
    {
        $catalog = [];
        foreach ($this->providers() as $moduleId => $provider) {
            $targets = [];
            $candidates = [];
            foreach ($provider->menuTargets() as $target) {
                $targets[] = [
                    'route' => $target->route,
                    'label' => $target->label,
                    'slugParam' => $target->slugParam,
                ];
                $list = [];
                foreach ($provider->menuCandidates($target->route) as $slug => $label) {
                    $list[] = [
                        'slug' => (string)$slug,
                        'label' => (string)$label,
                        'url' => $this->buildUrl($target->route, $target->slugParam, (string)$slug),
                    ];
                }
                $candidates[$target->route] = $list;
            }
            if ($targets !== []) {
                $catalog[$moduleId] = ['label' => $moduleId, 'targets' => $targets, 'candidates' => $candidates];
            }
        }
        return $catalog;
    }

    /**
     * Строит фронтовый URL цели по slug. При неудаче (роут не резолвится) — безопасный фолбэк с
     * query-параметром, чтобы страница не падала из-за одной проблемной цели.
     */
    private function buildUrl(string $route, string $slugParam, string $slug): string
    {
        try {
            return $this->getFrontendRoute($route, [$slugParam => $slug]);
        } catch (Throwable) {
            return '/' . ltrim($route, '/') . '?' . $slugParam . '=' . rawurlencode($slug);
        }
    }
}
