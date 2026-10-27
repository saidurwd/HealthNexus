<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Blood\BloodDonation;
use App\Models\Blood\BloodIncident;
use App\Models\Blood\BloodIssue;
use App\Models\Blood\BloodStorageLocation;
use App\Models\Blood\BloodUnit;
use App\Models\Ed\EdVisit;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\Blood\BloodCompatibilityService;
use App\Services\Blood\BloodIncidentService;
use App\Services\Blood\BloodInventoryService;
use App\Services\Blood\BloodIssueService;
use App\Services\Blood\BloodMtpService;
use App\Services\Blood\BloodRequestService;
use App\Services\Blood\BloodTestingService;
use App\Services\Blood\BloodTransfusionService;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Blood bank, second half, one day at a time: tested donations are released onto the shelf, fridges are read, bags expire, and requests from the
 * wards and the emergency department go through sample, cross-match, issue, bedside check and transfusion — with returns, a few reactions,
 * discards and the occasional massive transfusion protocol. Runs after Laboratory, which resulted the donation tests.
 */
class BloodTransfusionHistory implements DemoHistoryModule
{
    public function __construct(
        private readonly BloodTestingService $testing,
        private readonly BloodInventoryService $inventory,
        private readonly BloodRequestService $requests,
        private readonly BloodCompatibilityService $compatibility,
        private readonly BloodIssueService $issues,
        private readonly BloodTransfusionService $transfusions,
        private readonly BloodIncidentService $incidents,
        private readonly BloodMtpService $mtp,
    ) {}

    public function name(): string
    {
        return 'Blood bank — use';
    }

    public function module(): string
    {
        return 'bloodbank';
    }

    public function run(HistoryContext $context): string
    {
        $orgId = $context->organization->id;
        $tech = $context->staff('blood_bank_technician');
        $officer = $context->staff('blood_bank_medical_officer')->first();
        $nurses = $context->staff('staff_nurse');
        $doctor = $context->staff('doctor')->first();
        $locations = BloodStorageLocation::where('organization_id', $orgId)->get();
        $wards = collect($context->shared('ipd_admissions'));
        $counts = ['released' => 0, 'requests' => 0, 'transfused' => 0, 'returned' => 0, 'reactions' => 0, 'unfilled' => 0, 'expired' => 0, 'discarded' => 0, 'excursions' => 0, 'mtp' => 0];
        $recent = $context->to->subDay()->startOfDay();

        foreach ($context->days() as $day) {
            $staff = $context->pick($tech);

            // 1. yesterday's donations whose tests are in: release the clean ones, discard the blocked ones
            $context->at($day->setTime(9, 0), function () use ($orgId, $staff, $day, &$counts) {
                BloodDonation::query()->where('organization_id', $orgId)->where('status', 'collected')->whereDate('collected_at', '<', $day->toDateString())->each(function (BloodDonation $donation) use ($staff, &$counts) {
                    if (BloodUnit::where('donation_id', $donation->id)->where('status', BloodUnit::QUARANTINED)->exists()) {
                        try {
                            $counts['released'] += $this->testing->release($donation, $staff);
                        } catch (ValidationException) {
                            // tests not all resulted yet — try again tomorrow
                        }
                    }
                    BloodUnit::where('donation_id', $donation->id)->where('status', BloodUnit::BLOCKED)->where('blocked_reason', 'positive_screen')->get()->each(function (BloodUnit $unit) use ($staff, &$counts) {
                        $this->issues->discard($unit, 'positive_screen', $staff, 'Reactive screening test');
                        $counts['discarded']++;
                    });
                });
            });

            // 2. fridge readings twice a day; rarely a door left open
            foreach ([8, 20] as $hour) {
                foreach ($locations->where('kind', '!=', 'quarantine') as $location) {
                    $mid = ((float) $location->min_temp_c + (float) $location->max_temp_c) / 2;
                    $bad = $hour === 20 && $context->chance(1) && $location->kind === 'fridge';
                    $reading = $bad ? (float) $location->max_temp_c + 3.5 : round($mid + mt_rand(-10, 10) / 10, 1);
                    $log = $context->at($day->setTime($hour, 5), fn () => $this->inventory->recordTemperature($location->refresh(), $reading, $staff));
                    $log->is_excursion && $counts['excursions']++;
                }
            }
            // after an excursion the medical officer discards what was blocked by it
            $context->at($day->setTime(11, 0), function () use ($orgId, $officer, &$counts) {
                BloodUnit::where('organization_id', $orgId)->where('status', BloodUnit::BLOCKED)->where('blocked_reason', 'temperature_excursion')->get()->each(function (BloodUnit $unit) use ($officer, &$counts) {
                    $this->issues->discard($unit, 'temperature_excursion', $officer, 'Held outside the safe range');
                    $counts['discarded']++;
                });
            });

            // 3. expiry and lapsed reservations
            $context->at($day->setTime(7, 0), function () use ($orgId, &$counts) {
                $counts['expired'] += $this->inventory->expireDue($orgId);
                $this->issues->releaseLapsedReservations();
            });

            // 4. requests from the wards (about one a day) and the odd one from the emergency department
            $todays = $wards->filter(fn ($a) => CarbonImmutable::parse($a['at'])->toDateString() === $day->subDay()->toDateString())->take(2);
            foreach ($todays as $admission) {
                if ($context->chance(60)) {
                    $this->serve($context, Patient::findOrFail($admission['patient_id']), $day->setTime(10, 0)->addMinutes(mt_rand(0, 180)), 'ipd', $admission['encounter_id'], $tech, $nurses, $doctor, $day->gte($recent), $counts);
                }
            }
            if ($context->chance(25)) {
                $visit = EdVisit::query()->where('organization_id', $orgId)->whereDate('arrival_at', $day->toDateString())->whereNotNull('triage_level')->where('triage_level', '<=', 2)->inRandomOrder()->first();
                if ($visit) {
                    $this->serve($context, Patient::findOrFail($visit->patient_id), CarbonImmutable::parse($visit->arrival_at)->addHour(), 'ed', $visit->encounter_id, $tech, $nurses, $doctor, $day->gte($recent), $counts, true);
                }
            }
            if ($context->chance(4) && $day->lt($recent)) {
                $visit = EdVisit::query()->where('organization_id', $orgId)->whereDate('arrival_at', $day->toDateString())->where('triage_level', 1)->first();
                if ($visit) {
                    $context->at($day->setTime(14, 0), fn () => $this->mtp->activate($doctor, Patient::findOrFail($visit->patient_id), $doctor->name, 'Haemorrhagic shock — massive transfusion protocol', 'ed', $visit->encounter));
                    $counts['mtp']++;
                }
            }

            // 5. close old incidents with a resolution
            $context->at($day->setTime(16, 0), function () use ($orgId, $officer, $day) {
                BloodIncident::query()->where('organization_id', $orgId)->where('status', 'open')->where('occurred_at', '<', $day->subDays(3))->get()->each(fn (BloodIncident $i) => $this->incidents->close($i, 'Reviewed by the medical officer; no further action needed.', $officer));
            });
        }

        // The hourly housekeeping job would have caught anything that expired since the last simulated day.
        $counts['expired'] += $this->inventory->expireDue($orgId);
        $this->issues->releaseLapsedReservations();

        return sprintf('%d bags released, %d requests (%d transfused, %d returned, %d unfilled), %d reactions, %d expired, %d discarded, %d fridge excursions, %d massive protocols',
            $counts['released'], $counts['requests'], $counts['transfused'], $counts['returned'], $counts['unfilled'], $counts['reactions'], $counts['expired'], $counts['discarded'], $counts['excursions'], $counts['mtp']);
    }

    private function serve(HistoryContext $context, Patient $patient, CarbonImmutable $start, string $source, ?int $encounterId, $tech, $nurses, $doctor, bool $recent, array &$counts, bool $urgent = false): void
    {
        // a patient with an unresolved reaction gets no further blood until the blood-bank doctor has closed it
        if (BloodIncident::where('patient_id', $patient->id)->where('incident_type', 'transfusion_reaction')->where('status', '!=', 'closed')->exists()) {
            return;
        }
        $component = $context->pick(['prbc', 'prbc', 'prbc', 'prbc', 'ffp', 'platelets']);
        $units = mt_rand(1, 2);
        $encounter = $encounterId ? Encounter::find($encounterId) : null;
        $staff = $context->pick($tech);
        $request = $context->at($start, fn () => $this->requests->create($doctor, $patient, $component, $units, $urgent ? 'urgent' : 'routine', $source, $encounter, ['indication' => $context->pick(['Anaemia', 'Post-operative blood loss', 'Gastrointestinal bleeding', 'Coagulopathy', 'Low platelets'])]));
        $counts['requests']++;

        $group = $this->trueGroup($patient, $context);
        $collector = $context->pick($nurses);
        foreach ([$start->addMinutes(20), $start->addMinutes(40)] as $when) {
            $sample = $context->at($when->addMinutes(5), fn () => $this->requests->receiveSample($request->refresh(), $staff, ['patient_public_id' => $patient->public_id, 'abo' => $group[0], 'rh' => $group[1], 'collected_at' => $when, 'collected_by' => $collector->id]));
            if ($sample->status === 'accepted' && $this->requests->confirmedGroup($patient)) {
                break;
            }
        }
        if ($recent && $context->chance(50)) {
            return; // the newest requests are still waiting for blood
        }

        $filled = 0;
        $at = $start->addHour();
        for ($i = 0; $i < $units; $i++) {
            // a reaction to the first bag stops the rest of this request until the blood-bank doctor has closed the incident
            if (BloodIncident::where('patient_id', $patient->id)->where('incident_type', 'transfusion_reaction')->where('status', '!=', 'closed')->exists()) {
                break;
            }
            $candidate = BloodUnit::query()->where('organization_id', $patient->organization_id)->where('component', $component)->where('status', BloodUnit::AVAILABLE)->where('expires_at', '>', $at)->orderBy('expires_at')->get()
                ->first(fn (BloodUnit $u) => $this->compatibility->incompatibility($request->refresh(), $u, ['abo' => $group[0], 'rh' => $group[1]]) === null);
            if (! $candidate) {
                break;
            }
            try {
                $context->at($at, fn () => $this->compatibility->crossmatchAndReserve($request->refresh(), $candidate, $staff));
                $issue = $context->at($at->addMinutes(30), fn () => $this->issues->issue($request->refresh(), $candidate->refresh(), $staff, 'Ward attendant', round(mt_rand(30, 70) / 10, 1)));
            } catch (ValidationException) {
                break;
            }
            $filled++;
            $this->bedside($context, $issue, $candidate, $patient, $nurses, $staff, $at->addMinutes(75), $counts);
        }
        if ($filled === 0 && ! BloodIncident::where('patient_id', $patient->id)->where('incident_type', 'transfusion_reaction')->exists()) {
            $context->at($start->addHours(6), fn () => $this->requests->cancel($request->refresh(), $doctor, 'No compatible blood available; request withdrawn'));
            $counts['unfilled']++;
        }
    }

    private function bedside(HistoryContext $context, BloodIssue $issue, BloodUnit $unit, Patient $patient, $nurses, $staff, CarbonImmutable $at, array &$counts): void
    {
        if ($context->chance(5)) {
            $context->at($at, fn () => $this->issues->returnUnit($issue->refresh(), $staff, $context->chance(70) ? mt_rand(10, 25) : mt_rand(35, 90), round(mt_rand(30, 80) / 10, 1), 'Transfusion not needed'));
            $counts['returned']++;

            return;
        }
        $a = $context->pick($nurses);
        $b = $nurses->firstWhere(fn ($n) => $n->id !== $a->id) ?? $a;
        if ($a->id === $b->id) {
            $b = $context->actor;
        }
        $transfusion = $context->at($at, fn () => $this->transfusions->start($issue->refresh(), $a, $b, $patient->public_id, $unit->unit_number, null, ['pulse' => mt_rand(66, 96), 'temperature' => 36.8, 'systolic' => mt_rand(105, 140), 'diastolic' => mt_rand(65, 90)]));
        $context->at($at->addMinutes(15), fn () => $this->transfusions->recordObservations($transfusion->refresh(), $a, ['pulse' => mt_rand(66, 98), 'temperature' => 36.9]));

        if ($context->chance(3)) {
            $severe = $context->chance(8);
            $context->at($at->addMinutes(25), fn () => $this->transfusions->reportReaction($transfusion->refresh(), $a, $severe ? 'acute_haemolytic' : $context->pick(['febrile_non_haemolytic', 'allergic']), $severe ? 'severe' : $context->pick(['mild', 'moderate']), $severe ? ['fever', 'back pain'] : ['chills', 'itching'], 'Transfusion stopped, doctor informed'));
            $counts['reactions']++;

            return;
        }
        $context->at($at->addMinutes(150), fn () => $this->transfusions->complete($transfusion->refresh(), $a, $unit->volume_ml));
        $counts['transfused']++;
    }

    /** @return array{0: string, 1: string} the patient's own group if the record has one, otherwise a typical one */
    private function trueGroup(Patient $patient, HistoryContext $context): array
    {
        if (preg_match('/^(AB|A|B|O)([+-])$/', (string) $patient->blood_group, $m)) {
            return [$m[1], $m[2] === '+' ? 'positive' : 'negative'];
        }
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 38 => ['O', 'positive'], $roll <= 66 => ['B', 'positive'], $roll <= 88 => ['A', 'positive'], $roll <= 94 => ['AB', 'positive'], default => ['O', 'negative']
        };
    }
}
