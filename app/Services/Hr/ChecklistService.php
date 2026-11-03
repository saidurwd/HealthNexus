<?php

namespace App\Services\Hr;

use App\Models\Hr\HrChecklist;
use App\Models\Hr\HrChecklistItem;
use App\Models\Hr\HrEmployee;
use App\Models\Provider;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Onboarding and offboarding checklists (S03). Finishing the offboarding checklist is what switches the person's access off. */
class ChecklistService
{
    public function __construct(private readonly HrPolicy $policy, private readonly AuditLogger $audit) {}

    public function open(HrEmployee $employee, string $kind, User $by): HrChecklist
    {
        if (! in_array($kind, ['onboarding', 'offboarding'], true)) {
            throw ValidationException::withMessages(['kind' => 'Use onboarding or offboarding.']);
        }
        $existing = HrChecklist::query()->where(['employee_id' => $employee->id, 'kind' => $kind, 'status' => 'open'])->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($employee, $kind, $by) {
            $checklist = HrChecklist::create(['organization_id' => $employee->organization_id, 'employee_id' => $employee->id, 'kind' => $kind, 'created_by' => $by->id]);
            foreach ((array) $this->policy->get($kind.'_items') as $item) {
                $checklist->items()->create(['code' => $item['code'], 'title' => $item['title'], 'owner_role' => $item['owner_role'] ?? null, 'required' => $item['required'] ?? true]);
            }

            return $checklist->refresh();
        });
    }

    public function tick(HrChecklistItem $item, User $by, ?string $note = null): HrChecklist
    {
        return DB::transaction(function () use ($item, $by, $note) {
            $item = HrChecklistItem::query()->lockForUpdate()->findOrFail($item->id);
            $checklist = HrChecklist::query()->lockForUpdate()->findOrFail($item->checklist_id);
            if ($checklist->status === 'done') {
                throw ValidationException::withMessages(['checklist' => 'This checklist is already finished.']);
            }
            $item->update(['done_at' => now(), 'done_by' => $by->id, 'note' => $note]);
            if (! $checklist->items()->where('required', true)->whereNull('done_at')->exists()) {
                $checklist->update(['status' => 'done', 'completed_at' => now()]);
                if ($checklist->kind === 'offboarding') {
                    $this->revokeAccess($checklist->employee, $by, 'Offboarding checklist finished');
                }
            }

            return $checklist->refresh();
        });
    }

    /** Switches off the person's login, roles and tokens, and their provider record. Idempotent. */
    public function revokeAccess(HrEmployee $employee, User $by, string $reason): bool
    {
        $user = $employee->user;
        $changed = false;
        if ($user && $user->is_active) {
            $user->forceFill(['is_active' => false])->save();
            $user->syncRoles([]);
            $user->syncPermissions([]);
            $user->tokens()->delete();
            $changed = true;
        }
        Provider::query()->where('employee_id', $employee->id)->where('status', 'active')->update(['status' => 'inactive']);
        $changed && $this->audit->log('HR_ACCESS_REVOKED', HrEmployee::class, $employee->id, null, ['by' => $by->id, 'reason' => $reason]);

        return $changed;
    }
}
