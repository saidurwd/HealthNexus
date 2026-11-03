<?php

namespace App\Services\Watch;

use App\Models\Watch\SvCondition;

/** The starting list of conditions to watch (examples from config/watch.php, for the hospital to confirm with the health authority). Safe to run again; it never changes a condition the hospital has already edited. */
class WatchInstaller
{
    /** @return array{conditions:int} how many were added */
    public function install(int $organizationId): array
    {
        $n = 0;
        foreach ((array) config('watch.conditions') as $code => [$name, $bn, $prefixes, $window, $min, $factor, $cluster, $severity]) {
            $made = SvCondition::query()->firstOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => $name, 'name_bn' => $bn, 'icd_prefixes' => $prefixes, 'window_days' => $window, 'min_cases' => $min, 'spike_factor' => $factor, 'cluster_cases' => $cluster, 'severity' => $severity, 'active' => true]);
            $made->wasRecentlyCreated && $n++;
        }

        return ['conditions' => $n];
    }
}
