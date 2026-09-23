<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabPanel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A panel references existing lab_tests rows by FK — it never duplicates test definitions.
 */
class LabPanelService
{
    public function create(array $data, array $testIds, User $user): LabPanel
    {
        return DB::transaction(function () use ($data, $testIds, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            $panel = LabPanel::create($data);

            $this->syncTests($panel, $testIds);

            return $panel;
        });
    }

    public function update(LabPanel $panel, array $data, array $testIds, User $user): LabPanel
    {
        return DB::transaction(function () use ($panel, $data, $testIds) {
            if (isset($data['code']) && $data['code'] !== $panel->code) {
                $this->assertUniqueCode($panel->company_id, $panel->branch_id, $data['code'], $panel->id);
            }

            $panel->update($data);
            $this->syncTests($panel, $testIds);

            return $panel->refresh();
        });
    }

    public function deactivate(LabPanel $panel): void
    {
        $panel->update(['is_active' => false]);
        $panel->delete();
    }

    private function syncTests(LabPanel $panel, array $testIds): void
    {
        $panel->items()->delete();

        foreach (array_values($testIds) as $sequence => $testId) {
            $panel->items()->create(['test_id' => $testId, 'sequence' => $sequence]);
        }
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = LabPanel::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This panel code is already in use.']);
        }
    }
}
