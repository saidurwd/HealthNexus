<?php

namespace App\Services\Hr;

use App\Models\Hr\HrEmployee;
use App\Models\User;

/** Data migration (S12): turns the staff logins that already exist into employee records so HR starts with a register, then proposes provider links for review. */
class HrBackfillService
{
    public function __construct(private readonly EmployeeService $employees, private readonly ProviderLinkService $links) {}

    /** @return array{created: int, skipped: int, proposed: int} */
    public function fromUsers(int $organizationId, User $by, bool $dryRun = false): array
    {
        $created = $skipped = 0;
        $linked = HrEmployee::query()->whereNotNull('user_id')->pluck('user_id')->all();
        User::query()->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organizationId))->whereNotIn('id', $linked)->where('is_active', true)->has('roles')->orderBy('id')->each(function (User $user) use ($organizationId, $by, $dryRun, &$created, &$skipped) {
            if (str_contains((string) $user->email, 'noaccess@')) {
                $skipped++;

                return;
            }
            if ($dryRun) {
                $created++;

                return;
            }
            $parts = preg_split('/\s+/', trim((string) $user->name), 2);
            $employee = $this->employees->create($by, $organizationId, ['first_name' => $parts[0] ?: 'Staff', 'last_name' => $parts[1] ?? null, 'email' => $user->email, 'user_id' => $user->id, 'joined_on' => $user->created_at?->toDateString() ?? now()->toDateString()]);
            $employee->update(['status' => 'active']);
            $created++;
        });

        return ['created' => $created, 'skipped' => $skipped, 'proposed' => $dryRun ? 0 : $this->links->propose($organizationId)];
    }
}
