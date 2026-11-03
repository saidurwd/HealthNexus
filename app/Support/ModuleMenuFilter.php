<?php

namespace App\Support;

use App\Services\ModuleManager;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

/**
 * Hides a menu item while its module is switched off (guideline 14.1). 'module' => '<key>' is for one module; 'modules' => ['<key>', …] is for a section header and keeps it only while at least one of those
 * modules is on, so a section never shows with nothing under it.
 */
class ModuleMenuFilter implements FilterInterface
{
    public function transform($item)
    {
        $modules = app(ModuleManager::class);
        if (isset($item['module']) && ! $modules->isEnabled($item['module'])) {
            $item['restricted'] = true;
        }
        if (! empty($item['modules']) && collect((array) $item['modules'])->every(fn ($key) => ! $modules->isEnabled($key))) {
            $item['restricted'] = true;
        }

        return $item;
    }
}
