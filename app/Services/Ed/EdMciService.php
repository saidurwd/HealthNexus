<?php

namespace App\Services\Ed;

use App\Models\Ed\EdMciActivation;
use App\Models\Ed\EdVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Mass-casualty mode for a branch. While active, every new arrival is flagged and the board shows counts by triage level. */
class EdMciService
{
    public function active(int $organizationId, int $branchId): ?EdMciActivation
    {
        return EdMciActivation::query()->where('organization_id', $organizationId)->where('branch_id', $branchId)->whereNull('deactivated_at')->latest('activated_at')->first();
    }

    public function activate(User $by, int $organizationId, int $branchId, ?string $reason = null): EdMciActivation
    {
        return DB::transaction(function () use ($by, $organizationId, $branchId, $reason) {
            return $this->active($organizationId, $branchId) ?? EdMciActivation::create([
                'organization_id' => $organizationId, 'branch_id' => $branchId, 'reason' => $reason, 'activated_at' => now(), 'activated_by' => $by->id,
            ]);
        });
    }

    public function deactivate(User $by, int $organizationId, int $branchId): void
    {
        $active = $this->active($organizationId, $branchId) ?? throw ValidationException::withMessages(['mci' => 'Mass-casualty mode is not active.']);
        $active->update(['deactivated_at' => now(), 'deactivated_by' => $by->id]);
    }

    /** @return array<string, int> triage level (or 'untriaged') => active mass-casualty patients */
    public function counts(int $organizationId, int $branchId): array
    {
        $rows = EdVisit::query()->active()->where('organization_id', $organizationId)->where('branch_id', $branchId)->where('is_mass_casualty', true)
            ->selectRaw('triage_level, count(*) as total')->groupBy('triage_level')->pluck('total', 'triage_level');

        $out = [];
        foreach ($rows as $level => $total) {
            $out[(string) $level === '' ? 'untriaged' : (string) $level] = (int) $total;
        }
        ksort($out);

        return $out;
    }
}
