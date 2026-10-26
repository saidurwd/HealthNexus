<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Ipd\IpdAdmission;
use App\Models\Ipd\IpdAdmissionType;
use App\Models\Ipd\IpdBed;
use App\Models\Provider;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Ipd\IpdAdmissionService;
use App\Services\Ipd\IpdBedStatusService;
use App\Services\Ipd\IpdChargeService;
use App\Services\Ipd\IpdDischargeService;
use App\Services\Ipd\IpdTransferService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Day by day the ward fills and empties: admissions into free beds (only a free bed is ever used), daily bed charges, the odd transfer,
 * and discharges with all three clearances. Whoever is still inside on the last day stays admitted — the current inpatients.
 */
class IpdHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly IpdAdmissionService $admissions,
        private readonly IpdDischargeService $discharges,
        private readonly IpdTransferService $transfers,
        private readonly IpdChargeService $charges,
        private readonly IpdBedStatusService $bedStatus,
    ) {}

    public function name(): string
    {
        return 'IPD';
    }

    public function module(): string
    {
        return 'ipd';
    }

    public function run(HistoryContext $context): string
    {
        $doctors = $context->staff('doctor')->map(fn ($u) => Provider::firstOrCreate(
            ['organization_id' => $context->organization->id, 'user_id' => $u->id, 'provider_type' => 'doctor'],
            ['branch_id' => $context->branch->id, 'provider_code' => 'DOC-'.$u->id, 'name' => $u->name, 'status' => 'active'],
        ));
        $types = IpdAdmissionType::where('organization_id', $context->organization->id)->get();
        $patients = $context->patients();
        $coordinator = $context->staff('ipd_coordinator')->first();
        $perDay = max(1, intdiv($context->visitsPerDay, 5));
        $active = []; // admission id => planned discharge day
        $all = [];
        $counts = ['admitted' => 0, 'discharged' => 0, 'transfers' => 0, 'no_bed' => 0];

        foreach ($context->days() as $day) {
            // 1. discharges due today
            foreach ($active as $id => $due) {
                if ($due->lte($day)) {
                    $this->discharge($context, IpdAdmission::findOrFail($id), $day, $coordinator);
                    unset($active[$id]);
                    $counts['discharged']++;
                }
            }

            // 2. admissions
            for ($n = 0; $n < mt_rand(max(0, $perDay - 1), $perDay + 1); $n++) {
                $time = $context->workTime($day, $n * 45);
                $patient = $context->pick($patients);
                if (IpdAdmission::where('patient_id', $patient->id)->whereNotIn('status', ['discharged', 'closed', 'cancelled'])->exists()) {
                    continue;
                }
                $admission = $this->admit($context, $patient->id, $time, $doctors->isNotEmpty() ? $context->pick($doctors) : null, $types->isNotEmpty() ? $context->pick($types) : null);
                if (! $admission) {
                    $counts['no_bed']++;

                    continue;
                }
                $counts['admitted']++;
                $active[$admission->id] = $day->addDays(mt_rand(2, 8));
                $all[] = ['id' => $admission->id, 'patient_id' => $patient->id, 'encounter_id' => $admission->encounter_id, 'at' => $time->toIso8601String()];
            }

            // 3. daily bed charge for everyone inside, and the odd transfer
            foreach (array_keys($active) as $id) {
                $admission = IpdAdmission::with('currentAllocation.bed')->find($id);
                $bed = $admission?->currentAllocation?->bed;
                if (! $bed) {
                    continue;
                }
                $context->at($day->setTime(6, 0), fn () => $this->charges->chargeBedDay($admission, $bed, $day->toDateString(), $context->actor));
                if ($context->chance(4) && $admission->admitted_at->lt($day->startOfDay())) {
                    $counts['transfers'] += $this->transfer($context, $admission, $day) ? 1 : 0;
                }
            }
        }

        $context->share('ipd_admissions', $all);

        return sprintf('%d admissions (%d discharged, %d still inside), %d transfers, %d refused for lack of a bed', $counts['admitted'], $counts['discharged'], count($active), $counts['transfers'], $counts['no_bed']);
    }

    private function admit(HistoryContext $context, int $patientId, CarbonImmutable $time, ?Provider $doctor, $type): ?IpdAdmission
    {
        $beds = IpdBed::where('organization_id', $context->organization->id)->where('status', IpdBed::STATUS_AVAILABLE)->where('is_active', true)->inRandomOrder()->limit(5)->get();
        foreach ($beds as $bed) {
            try {
                return $context->at($time, fn () => $this->admissions->admit([
                    'organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'patient_id' => $patientId, 'bed_id' => $bed->id,
                    'admission_type_id' => $type?->id, 'attending_provider_id' => $doctor?->id, 'admitting_provider_id' => $doctor?->id, 'priority' => $context->chance(20) ? 'urgent' : 'routine',
                ], $context->actor));
            } catch (ValidationException) {
                continue;
            }
        }

        return null;
    }

    private function discharge(HistoryContext $context, IpdAdmission $admission, CarbonImmutable $day, $coordinator): void
    {
        $at = $day->setTime(11, 0);
        $bed = $admission->currentAllocation?->bed;
        $request = $context->at($at, fn () => $this->discharges->request($admission, ['discharge_type' => 'routine'], $coordinator));
        foreach (['clinical', 'billing', 'pharmacy'] as $i => $type) {
            $request = $context->at($at->addMinutes(20 * ($i + 1)), fn () => $this->discharges->recordClearance($request->refresh(), $type, $coordinator));
        }
        $context->at($at->addHours(2), fn () => $this->discharges->complete($request->refresh(), $coordinator));
        if ($bed && $bed->refresh()->status === IpdBed::STATUS_CLEANING) {
            $context->at($at->addHours(3), fn () => $this->bedStatus->transitionTo($bed, IpdBed::STATUS_AVAILABLE, $context->actor, 'Cleaned'));
        }
    }

    private function transfer(HistoryContext $context, IpdAdmission $admission, CarbonImmutable $day): bool
    {
        $target = IpdBed::where('organization_id', $admission->organization_id)->where('status', IpdBed::STATUS_AVAILABLE)->where('is_active', true)->inRandomOrder()->first();
        if (! $target) {
            return false;
        }
        $at = $day->setTime(14, 0);
        try {
            $movement = $context->at($at, fn () => $this->transfers->request($admission, $target, $context->actor, reason: 'Moved for clinical reasons'));
            $context->at($at->addMinutes(15), fn () => $this->transfers->approve($movement, $context->actor));
            $context->at($at->addMinutes(30), fn () => $this->transfers->complete($movement->refresh(), $context->actor));
        } catch (ValidationException) {
            return false;
        }

        return true;
    }
}
