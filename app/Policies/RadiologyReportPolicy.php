<?php

namespace App\Policies;

use App\Models\Radiology\RadiologyReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RadiologyReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('radiology.report.view');
    }

    public function view(User $user, RadiologyReport $report): bool
    {
        return $user->can('radiology.report.view') && $this->inScope($user, $report);
    }

    public function create(User $user): bool
    {
        return $user->can('radiology.report.create');
    }

    public function update(User $user, RadiologyReport $report): bool
    {
        return $user->can('radiology.report.update') && $this->inScope($user, $report);
    }

    public function submit(User $user, RadiologyReport $report): bool
    {
        return $user->can('radiology.report.submit') && $this->inScope($user, $report);
    }

    public function approve(User $user, RadiologyReport $report): bool
    {
        return $user->can('radiology.report.approve') && $this->inScope($user, $report);
    }

    public function amend(User $user, RadiologyReport $report): bool
    {
        return $user->can('radiology.report.amend') && $this->inScope($user, $report);
    }

    private function inScope(User $user, RadiologyReport $report): bool
    {
        return $user->companies()->where('companies.id', $report->company_id)->exists()
            && $user->branches()->where('branches.id', $report->branch_id)->exists();
    }
}
