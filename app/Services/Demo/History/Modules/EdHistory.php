<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Ed\EdBay;
use App\Models\Ed\EdVisit;
use App\Models\Ed\EdZone;
use App\Models\Laboratory\LabTest;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Ed\EdBayService;
use App\Services\Ed\EdConsultService;
use App\Services\Ed\EdDispositionService;
use App\Services\Ed\EdMonitoringService;
use App\Services\Ed\EdObservationService;
use App\Services\Ed\EdOrderService;
use App\Services\Ed\EdProtocolService;
use App\Services\Ed\EdResuscitationService;
use App\Services\Ed\EdTriageService;
use App\Services\Ed\EdVisitService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Emergency arrivals every day, in all their variety: walk-ins and ambulances, unknown patients, every triage level, and every way of leaving. */
class EdHistory implements DemoHistoryModule
{
    private const COMPLAINTS = ['Chest pain', 'Road traffic accident', 'Fall with injury', 'Severe abdominal pain', 'Difficulty breathing', 'High fever', 'Burn injury', 'Seizure', 'Vomiting and diarrhoea', 'Snake bite', 'Weakness of one side', 'Heavy bleeding'];

    /** disposition => share (%) */
    private const OUTCOMES = ['discharge' => 66, 'admit_ipd' => 12, 'lwbs' => 5, 'lama' => 4, 'transfer' => 4, 'icu' => 3, 'surgery' => 3, 'referral' => 2, 'death' => 1];

    public function __construct(
        private readonly EdVisitService $visits,
        private readonly EdTriageService $triage,
        private readonly EdBayService $bays,
        private readonly EdResuscitationService $resus,
        private readonly EdMonitoringService $monitoring,
        private readonly EdOrderService $orders,
        private readonly EdProtocolService $protocols,
        private readonly EdConsultService $consults,
        private readonly EdObservationService $observation,
        private readonly EdDispositionService $dispositions,
    ) {}

    public function name(): string
    {
        return 'Emergency';
    }

    public function module(): string
    {
        return 'ed';
    }

    public function run(HistoryContext $context): string
    {
        $this->zones($context);
        $patients = $context->patients();
        $doctors = $context->staff('doctor');
        $nurses = $context->staff('staff_nurse');
        $tests = LabTest::query()->forTenant($context->organization->id)->where('is_active', true)->limit(10)->get();
        $perDay = max(1, intdiv($context->visitsPerDay, 2));
        $counts = ['arrivals' => 0, 'unknown' => 0, 'open' => 0, 'outcomes' => []];
        $recent = $context->to->subDay()->startOfDay();

        foreach ($context->days() as $day) {
            for ($n = 0; $n < mt_rand(max(0, $perDay - 2), $perDay + 2); $n++) {
                $arrival = $day->setTime(mt_rand(0, 23), mt_rand(0, 59));
                $doctor = $context->pick($doctors);
                $nurse = $context->pick($nurses);
                $unknown = $context->chance(6);
                $level = $this->level($context);
                $patient = $unknown ? null : $context->pick($patients);

                try {
                    $visit = $context->at($arrival, fn () => $this->visits->arrive($nurse, $context->branch, [
                        'patient_id' => $patient?->id, 'arrival_mode' => $level <= 2 ? $context->pick(['ambulance', 'ambulance', 'police', 'walk_in']) : $context->pick(['walk_in', 'walk_in', 'referral']),
                        'chief_complaint' => $context->pick(self::COMPLAINTS), 'sex' => $unknown ? $context->pick(['M', 'F']) : null, 'estimated_age' => $unknown ? mt_rand(18, 70) : null,
                    ]));
                } catch (ValidationException) {
                    continue; // the patient still has an open emergency visit
                }
                $counts['arrivals']++;
                $unknown && $counts['unknown']++;

                $this->treat($context, $visit, $level, $arrival, $doctor, $nurse, $tests, $arrival->gte($recent), $counts);
            }
        }

        arsort($counts['outcomes']);

        return sprintf('%d arrivals (%d unknown patients), %d still in the department; outcomes: %s', $counts['arrivals'], $counts['unknown'], $counts['open'], collect($counts['outcomes'])->map(fn ($n, $k) => "$k $n")->implode(', '));
    }

    private function treat(HistoryContext $context, EdVisit $visit, int $level, CarbonImmutable $arrival, $doctor, $nurse, $tests, bool $recent, array &$counts): void
    {
        $vitals = $level <= 2
            ? ['pulse' => mt_rand(110, 150), 'oxygen_saturation' => mt_rand(82, 92), 'systolic' => mt_rand(80, 100), 'diastolic' => mt_rand(45, 65), 'respiratory_rate' => mt_rand(24, 34), 'temperature' => 38.4]
            : ['pulse' => mt_rand(66, 105), 'oxygen_saturation' => mt_rand(95, 100), 'systolic' => mt_rand(105, 150), 'diastolic' => mt_rand(65, 95), 'respiratory_rate' => mt_rand(13, 20), 'temperature' => round(36.4 + mt_rand(0, 20) / 10, 1)];

        if ($recent && $context->chance(30)) {
            $counts['open']++;

            return; // just arrived, not yet triaged
        }
        $context->at($arrival->addMinutes(mt_rand(3, 20)), fn () => $this->triage->triage($visit, $nurse, ['level' => $level, 'complaint' => $visit->chief_complaint, 'vitals' => $vitals]));

        $bay = EdBay::where('organization_id', $context->organization->id)->where('status', 'available')->where('is_active', true)->orderBy($level <= 2 ? 'id' : 'zone_id')->first();
        $bayTime = $arrival->addMinutes(mt_rand(15, 40));
        $bay && $context->at($bayTime, fn () => $this->bays->assign($visit->refresh(), $bay, $nurse));
        $seen = $arrival->addMinutes(match (true) {
            $level <= 1 => mt_rand(0, 5), $level === 2 => mt_rand(5, 20), $level === 3 => mt_rand(20, 70), default => mt_rand(40, 130)
        });
        $context->at($seen, fn () => $this->visits->startTreatment($visit->refresh(), $doctor));

        if ($level === 1) {
            foreach (['arrival_resus', 'cpr_started', 'adrenaline_given', 'rosc'] as $i => $event) {
                $context->at($seen->addMinutes($i * 4), fn () => $this->resus->record($visit->refresh(), $nurse, $event));
            }
            $context->chance(40) && $context->at($seen->addMinutes(1), fn () => $this->resus->callCodeBlue($visit->refresh(), $nurse, 'Resus bay'));
        }
        $context->at($seen->addMinutes(20), fn () => $this->monitoring->record($visit->refresh(), $nurse, $level <= 2 ? ['pulse' => mt_rand(100, 140), 'oxygen_saturation' => mt_rand(88, 96)] : ['pulse' => mt_rand(65, 100)]));

        if ($tests->isNotEmpty() && $context->chance(55)) {
            $context->at($seen->addMinutes(10), fn () => $this->orders->placeOrder($visit->refresh(), $doctor, 'laboratory', $tests->random(min($tests->count(), 2))->map(fn ($t) => ['name' => $t->name, 'code' => $t->code])->all()));
        }
        $context->chance(15) && $context->at($seen->addMinutes(12), fn () => $this->orders->prescribe($visit->refresh(), $doctor, [['medicine_name' => 'Paracetamol 500mg Tablet', 'frequency' => '1+1+1', 'duration' => '3 days']]));
        $context->chance(20) && $context->at($seen->addMinutes(15), fn () => $this->orders->giveMedication($visit->refresh(), $nurse, ['drug_name' => $context->pick(['Normal Saline', 'Ondansetron', 'Ketorolac', 'Diclofenac']), 'dose' => $context->pick(['500 ml', '4 mg', '30 mg', '75 mg']), 'route' => 'IV']));
        $context->chance(12) && $context->at($seen->addMinutes(30), fn () => $this->orders->recordProcedure($visit->refresh(), $doctor, $context->pick(['Suturing', 'Splinting', 'Wound dressing', 'Catheterisation'])));
        if ($level <= 2 && $context->chance(30)) {
            $context->at($seen->addMinutes(5), fn () => $this->protocols->start($visit->refresh(), $doctor, $context->pick(['stroke', 'acs', 'sepsis', 'trauma'])));
        }
        if ($context->chance(14)) {
            $consult = $context->at($seen->addMinutes(25), fn () => $this->consults->request($visit->refresh(), $doctor, $context->pick(['Cardiology', 'Surgery', 'Orthopaedics', 'Neurology']), $level <= 2 ? 'emergent' : 'urgent', 'Specialist opinion'));
            $context->chance(85) && $context->at($seen->addMinutes(55), fn () => $this->consults->respond($consult, $doctor, 'Seen and advised.'));
        }
        $stay = $context->chance(10) ? $context->at($seen->addMinutes(40), fn () => $this->observation->start($visit->refresh(), $nurse)) : null;

        if ($recent && $context->chance(50)) {
            $counts['open']++;

            return;
        }

        $outcome = $this->outcome($context, $level);
        $closeAt = $seen->addMinutes($stay ? mt_rand(240, 900) : mt_rand(60, 360));
        $data = ['type' => $outcome, 'reason' => 'Decision after assessment', 'follow_up' => 'Return if worse'];
        match ($outcome) {
            'transfer', 'referral' => $data += ['destination' => 'Higher centre', 'ambulance_required' => true],
            'lama' => $data += ['risks_explained' => true],
            'death' => $data += ['time_of_death' => $closeAt->subMinutes(2)->toDateTimeString(), 'cause' => 'Cardiac arrest'],
            default => null,
        };
        try {
            $context->at($closeAt, fn () => $this->dispositions->decide($visit->refresh(), $doctor, $data));
            $counts['outcomes'][$outcome] = ($counts['outcomes'][$outcome] ?? 0) + 1;
        } catch (ValidationException) {
            $context->at($closeAt, fn () => $this->dispositions->decide($visit->refresh(), $context->actor, ['type' => 'discharge']));
            $counts['outcomes']['discharge'] = ($counts['outcomes']['discharge'] ?? 0) + 1;
        }
    }

    private function level(HistoryContext $context): int
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 3 => 1, $roll <= 15 => 2, $roll <= 50 => 3, $roll <= 85 => 4, default => 5
        };
    }

    private function outcome(HistoryContext $context, int $level): string
    {
        $roll = mt_rand(1, 100);
        $sum = 0;
        foreach (self::OUTCOMES as $outcome => $share) {
            $sum += $share;
            if ($roll <= $sum) {
                return $outcome === 'death' && $level > 2 ? 'discharge' : $outcome;
            }
        }

        return 'discharge';
    }

    private function zones(HistoryContext $context): void
    {
        foreach ([['RESUS', 'Resuscitation', 'resuscitation', 2], ['ACUTE', 'Acute care', 'acute', 6], ['MINOR', 'Minor injuries', 'minor', 4], ['OBS', 'Observation', 'observation', 4]] as $i => [$code, $name, $type, $bays]) {
            $zone = EdZone::firstOrCreate(['organization_id' => $context->organization->id, 'branch_id' => $context->branch->id, 'code' => $code], ['name' => $name, 'zone_type' => $type, 'sort_order' => $i, 'is_active' => true]);
            foreach (range(1, $bays) as $n) {
                EdBay::firstOrCreate(['organization_id' => $context->organization->id, 'zone_id' => $zone->id, 'code' => "$code-$n"], ['status' => 'available', 'is_active' => true]);
            }
        }
    }
}
