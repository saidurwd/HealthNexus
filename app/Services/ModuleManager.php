<?php

namespace App\Services;

use App\Models\OrganizationModule;
use Illuminate\Validation\ValidationException;

/**
 * Module enablement (guideline 14.1). Resolution order: hospital row, organization row,
 * registry default (built modules are on, unbuilt modules are off).
 */
class ModuleManager
{
    /** @var array<string, array<string, bool>> keyed by "org:hospital" */
    private array $cache = [];

    public function registry(): array
    {
        return config('modules');
    }

    public function exists(string $key): bool
    {
        return isset($this->registry()[$key]);
    }

    public function isEnabled(string $key, ?int $organizationId = null, ?int $hospitalId = null): bool
    {
        $module = $this->registry()[$key] ?? null;

        if ($module === null || ! $module['built']) {
            return false;
        }

        if ($module['always_on']) {
            return true;
        }

        $tenant = app(TenantContextResolver::class);
        $organizationId ??= $tenant->getOrganizationId();
        $hospitalId ??= $tenant->getHospitalId();

        if ($organizationId === null) {
            return true; // no tenant resolved (console, public pages): the middleware must not guess
        }

        return $this->overrides($organizationId, $hospitalId)[$key] ?? true;
    }

    public function set(string $key, bool $enabled, int $organizationId, ?int $hospitalId = null, ?int $userId = null): void
    {
        $module = $this->registry()[$key] ?? throw ValidationException::withMessages(['module' => "Unknown module [$key]."]);

        if ($module['always_on'] && ! $enabled) {
            throw ValidationException::withMessages(['module' => "{$module['name']} cannot be switched off."]);
        }

        if ($enabled && ! $module['built']) {
            throw ValidationException::withMessages(['module' => "{$module['name']} is not available yet."]);
        }

        if ($enabled) {
            foreach ($module['depends_on'] as $dependency) {
                if (! $this->isEnabled($dependency, $organizationId, $hospitalId)) {
                    throw ValidationException::withMessages(['module' => "Enable {$this->registry()[$dependency]['name']} first."]);
                }
            }
        } else {
            foreach ($this->registry() as $otherKey => $other) {
                if (in_array($key, $other['depends_on'], true) && $this->isEnabled($otherKey, $organizationId, $hospitalId)) {
                    throw ValidationException::withMessages(['module' => "Switch off {$other['name']} first."]);
                }
            }
        }

        OrganizationModule::updateOrCreate(
            ['organization_id' => $organizationId, 'hospital_id' => $hospitalId, 'module_key' => $key],
            ['is_enabled' => $enabled, 'changed_by' => $userId],
        );

        $this->cache = [];
    }

    /** @return array<string, bool> */
    private function overrides(int $organizationId, ?int $hospitalId): array
    {
        $cacheKey = $organizationId.':'.($hospitalId ?? 0);

        return $this->cache[$cacheKey] ??= OrganizationModule::query()
            ->where('organization_id', $organizationId)
            ->where(fn ($q) => $q->whereNull('hospital_id')->when($hospitalId, fn ($q2) => $q2->orWhere('hospital_id', $hospitalId)))
            ->orderByRaw('hospital_id is not null') // hospital rows last so they win
            ->get()
            ->mapWithKeys(fn ($row) => [$row->module_key => $row->is_enabled])
            ->all();
    }
}
