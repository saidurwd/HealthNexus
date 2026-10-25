<?php

namespace App\Support;

use App\Services\ModuleManager;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

/** Hides any menu item carrying 'module' => '<key>' while that module is switched off (guideline 14.1). */
class ModuleMenuFilter implements FilterInterface
{
    public function transform($item)
    {
        if (is_array($item) && isset($item['module']) && ! app(ModuleManager::class)->isEnabled($item['module'])) {
            return false;
        }

        return $item;
    }
}
