<?php

namespace App\Console\Commands\Hr;

use App\Models\Hr\HrDevice;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\CredentialService;
use App\Services\Hr\LeaveBalanceService;
use App\Services\Hr\PunchIngestService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

/** Nightly housekeeping for people and payroll: pull device punches, work out yesterday's attendance, accrue leave, mark and warn about lapsing licences. */
class CheckHr extends Command
{
    protected $signature = 'hr:check';

    protected $description = 'Sync attendance devices, work out yesterday\'s attendance, accrue leave on the 1st, mark lapsed licences and warn about those about to lapse';

    public function handle(PunchIngestService $punches, AttendanceService $attendance, LeaveBalanceService $balances, CredentialService $credentials, ModuleManager $modules): int
    {
        foreach (Organization::query()->where('is_active', true)->get() as $organization) {
            if (! $modules->isEnabled('hr', $organization->id)) {
                continue;
            }
            $synced = 0;
            HrDevice::query()->where(['organization_id' => $organization->id, 'is_active' => true])->each(function (HrDevice $device) use ($punches, &$synced) {
                $punches->sync($device) !== null && $synced++;
            });
            $days = $attendance->processRange($organization->id, now()->subDay(), now()->subDay());
            if (now()->day === 1) {
                now()->month === 1 && $balances->openYear($organization->id, now()->year);
                $balances->accrueMonth($organization->id, now()->subMonthNoOverflow());
            }
            $recipients = User::query()->where('is_active', true)->whereHas('organizations', fn ($q) => $q->where('organizations.id', $organization->id))->whereHas('roles', fn ($q) => $q->whereIn('name', ['hr_officer', 'hr_manager']))->limit(10)->pluck('id')->all();
            $result = $credentials->sweep($organization->id, $recipients);
            $this->info("{$organization->code}: $synced device(s) synced, $days attendance day(s) worked out, {$result['expired']} licence(s) lapsed, {$result['alerts']} warning(s).");
        }

        return self::SUCCESS;
    }
}
