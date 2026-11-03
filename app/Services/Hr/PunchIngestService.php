<?php

namespace App\Services\Hr;

use App\Contracts\Integration\BiometricGateway;
use App\Models\Hr\HrDevice;
use App\Models\Hr\HrEmployee;
use App\Models\Hr\HrPunch;
use App\Models\Hr\HrRosterEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Raw punches from attendance devices (S05, P-22). Punches are stored exactly as received and never edited; the same punch sent twice is stored once, so a
 * device that was offline can send its whole backlog later and nothing doubles. A code nobody has been mapped to yet is kept, unresolved, until someone maps it.
 */
class PunchIngestService
{
    public function __construct(private readonly BiometricGateway $gateway, private readonly AttendanceService $attendance) {}

    /**
     * @param  list<array{code: string, at: string}>  $punches
     * @return array{stored: int, duplicates: int, unresolved: int, days_recalculated: int}
     */
    public function ingest(int $organizationId, ?HrDevice $device, array $punches, string $source = 'device', bool $recalculate = true): array
    {
        $stored = $dupes = $unresolved = 0;
        $touched = [];
        $byCode = HrEmployee::query()->where('organization_id', $organizationId)->whereNotNull('device_code')->pluck('id', 'device_code');
        DB::transaction(function () use ($organizationId, $device, $punches, $source, $byCode, &$stored, &$dupes, &$unresolved, &$touched) {
            foreach ($punches as $p) {
                $at = CarbonImmutable::parse($p['at']);
                $key = ($device->code ?? 'manual').':'.$p['code'].':'.$at->format('YmdHis');
                $employeeId = $byCode[$p['code']] ?? null;
                $row = HrPunch::query()->firstOrCreate(['organization_id' => $organizationId, 'idempotency_key' => $key], ['device_id' => $device?->id, 'device_code' => $p['code'], 'employee_id' => $employeeId, 'punched_at' => $at, 'source' => $source, 'created_at' => now()]);
                if (! $row->wasRecentlyCreated) {
                    $dupes++;

                    continue;
                }
                $stored++;
                $employeeId === null ? $unresolved++ : $touched[$employeeId][$at->toDateString()] = true;
                // A punch just after midnight can belong to the day before (night shifts): recalculate both.
                if ($employeeId !== null && HrRosterEntry::query()->where('employee_id', $employeeId)->whereDate('work_date', $at->subDay()->toDateString())->exists()) {
                    $touched[$employeeId][$at->subDay()->toDateString()] = true;
                }
            }
            $device?->update(['last_sync_at' => now()]);
        });
        $days = 0;
        if ($recalculate) {
            foreach ($touched as $employeeId => $dates) {
                $employee = HrEmployee::find($employeeId);
                foreach (array_keys($dates) as $date) {
                    $employee && $this->attendance->calculateDay($employee, CarbonImmutable::parse($date)) && $days++;
                }
            }
            HrPunch::query()->where('organization_id', $organizationId)->whereNull('processed_at')->whereNotNull('employee_id')->update(['processed_at' => now()]);
        }

        return ['stored' => $stored, 'duplicates' => $dupes, 'unresolved' => $unresolved, 'days_recalculated' => $days];
    }

    /** Pulls from a device through the gateway; an unreachable device is skipped and caught up next time. */
    public function sync(HrDevice $device): ?array
    {
        $since = $device->last_sync_at ? CarbonImmutable::parse($device->last_sync_at)->subHour() : now()->subDay();
        $result = $this->gateway->pullPunches($device->code, $since);
        if (! $result->handled) {
            return null;
        }

        return $this->ingest($device->organization_id, $device, (array) ($result->data['punches'] ?? []));
    }

    /** Teaches the system whose a device number is, and applies it to the unresolved backlog. @return int punches resolved */
    public function mapCode(int $organizationId, string $code, HrEmployee $employee): int
    {
        if ($employee->device_code !== $code) {
            if (HrEmployee::query()->where('organization_id', $organizationId)->where('device_code', $code)->where('id', '!=', $employee->id)->exists()) {
                throw ValidationException::withMessages(['code' => 'That device number already belongs to another person.']);
            }
            $employee->update(['device_code' => $code]);
        }
        $punches = HrPunch::query()->where(['organization_id' => $organizationId, 'device_code' => $code])->whereNull('employee_id')->get();
        foreach ($punches as $punch) {
            $punch->update(['employee_id' => $employee->id]);
        }
        foreach ($punches->groupBy(fn ($p) => $p->punched_at->toDateString()) as $date => $_) {
            $this->attendance->calculateDay($employee, CarbonImmutable::parse($date));
        }
        HrPunch::query()->where('employee_id', $employee->id)->whereNull('processed_at')->update(['processed_at' => now()]);

        return $punches->count();
    }
}
