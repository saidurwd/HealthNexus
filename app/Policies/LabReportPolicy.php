<?php

namespace App\Policies;

use App\Models\Laboratory\LabReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('lab.report.view');
    }

    public function view(User $user, LabReport $report): bool
    {
        return $user->can('lab.report.view') && $this->inScope($user, $report);
    }

    public function generate(User $user): bool
    {
        return $user->can('lab.report.generate');
    }

    public function print(User $user, LabReport $report): bool
    {
        return $user->can('lab.report.print') && $this->inScope($user, $report);
    }

    private function inScope(User $user, LabReport $report): bool
    {
        return $user->companies()->where('companies.id', $report->company_id)->exists()
            && $user->branches()->where('branches.id', $report->branch_id)->exists();
    }
}
