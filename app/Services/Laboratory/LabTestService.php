<?php

namespace App\Services\Laboratory;

use App\Models\Laboratory\LabTest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD for the test catalog. Soft-delete only — lab_results reference lab_tests historically,
 * so a test is deactivated + soft-deleted rather than hard-deleted, mirroring BillingItemService.
 */
class LabTestService
{
    public function create(array $data, User $user): LabTest
    {
        return DB::transaction(function () use ($data, $user) {
            $this->assertUniqueCode($data['company_id'], $data['branch_id'] ?? null, $data['code']);

            return LabTest::create([
                ...$data,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function update(LabTest $test, array $data, User $user): LabTest
    {
        return DB::transaction(function () use ($test, $data, $user) {
            if (isset($data['code']) && $data['code'] !== $test->code) {
                $this->assertUniqueCode($test->company_id, $test->branch_id, $data['code'], $test->id);
            }

            $test->update([...$data, 'updated_by' => $user->id]);

            return $test->refresh();
        });
    }

    public function deactivate(LabTest $test, User $user): void
    {
        $test->update(['is_active' => false, 'updated_by' => $user->id]);
        $test->delete();
    }

    private function assertUniqueCode(int $companyId, ?int $branchId, string $code, ?int $exceptId = null): void
    {
        $exists = LabTest::query()
            ->forTenant($companyId, $branchId)
            ->where('code', $code)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => 'This test code is already in use.']);
        }
    }
}
