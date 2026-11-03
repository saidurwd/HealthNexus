<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Patient;
use App\Models\PatientAddress;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Vital\BirthService;
use App\Services\Vital\DeathService;
use App\Services\Vital\MedicoLegalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Births, deaths and police cases, six months. Mothers (women patients) have their births notified by the records officer, a second person (the records manager) checks and issues, and most families register the
 * birth with the registrar and the number is recorded; one is a stillbirth, one was cancelled and written again, a few wait for a second person and two were issued long ago and are still not registered. Patients who
 * died get a certificate: the records officer writes it, a doctor certifies the cause, the records manager checks the family member and issues it. Two deaths are accidents: a police case is opened with each, the police are
 * told, one case is closed (and its certificate then issued), the other is still open (so its certificate is held). A few injured living patients are police cases too, one of which the police were not told about in time.
 */
class VitalHistory implements DemoHistoryModule
{
    private const CAUSES = [['Septic shock', 'Pneumonia', 'Chronic kidney disease'], ['Acute myocardial infarction', 'Coronary artery disease', 'Hypertension'], ['Respiratory failure', 'Chronic obstructive pulmonary disease', null], ['Cerebral haemorrhage', 'Hypertension', null], ['Multi-organ failure', 'Severe sepsis', 'Diabetes mellitus']];

    public function __construct(private readonly BirthService $births, private readonly DeathService $deaths, private readonly MedicoLegalService $cases) {}

    public function name(): string
    {
        return 'Births, deaths and police cases';
    }

    public function module(): string
    {
        return 'vital';
    }

    public function run(HistoryContext $context): string
    {
        $clerk = $context->staff('records_officer')->first();
        $manager = $context->staff('records_manager')->first();
        $police = $context->staff('medicolegal_officer')->first();
        $doctors = $context->staff('doctor');
        $stats = ['births' => 0, 'stillbirths' => 0, 'registered' => 0, 'deaths' => 0, 'issued' => 0, 'police' => 0];

        $mothers = Patient::query()->where('organization_id', $context->organization->id)->whereIn('sex', ['F', 'female', 'Female'])->whereNull('deceased_at')->orderBy('id')->get();
        $stats += $this->births($context, $mothers, $clerk, $manager);
        $stats += $this->deaths($context, $clerk, $manager, $doctors, $police);
        $stats += $this->livingCases($context, $police);

        return sprintf('%d births notified (%d stillbirth, %d registered), %d death certificates (%d issued), %d police cases', $stats['births'], $stats['stillbirths'], $stats['registered'], $stats['deaths'], $stats['issued'], $stats['police']);
    }

    /** @param  Collection<int, Patient>  $mothers @return array<string,int> */
    private function births(HistoryContext $context, Collection $mothers, $clerk, $manager): array
    {
        $n = ['births' => 0, 'stillbirths' => 0, 'registered' => 0];
        $count = min($mothers->count(), 16);
        $span = max(1, (int) $context->from->diffInDays($context->to) - 2);
        foreach ($mothers->take($count)->values() as $i => $mother) {
            $born = $context->from->addDays((int) floor($i * $span / max(1, $count)))->setTime(mt_rand(1, 22), mt_rand(0, 59));
            $still = $i === 5;
            $notification = null;
            $context->at($born->addHours(3), function () use (&$notification, $mother, $born, $still, $context, $clerk) {
                $district = PatientAddress::query()->where(['patient_id' => $mother->id, 'address_type' => 'permanent'])->first();
                $notification = $this->births->draft($mother, [
                    'born_at' => $born, 'sex' => $context->pick(['male', 'female']), 'outcome' => $still ? 'stillbirth' : 'live_birth', 'place_of_birth' => 'this_hospital', 'delivery_type' => $context->pick(['normal', 'normal', 'caesarean', 'assisted']),
                    'weight_g' => mt_rand(2200, 3900), 'gestation_weeks' => $still ? 34 : mt_rand(37, 41), 'father_name' => 'Md '.$context->pick(['Karim', 'Rahim', 'Hossain', 'Uddin', 'Ali', 'Miah']), 'informant_name' => 'Family member',
                    'informant_relation' => $context->pick(['Father', 'Mother-in-law', 'Brother']), 'attendant_name' => 'Duty obstetrician', 'division_id' => $district?->division_id, 'district_id' => $district?->district_id, 'upazila_id' => $district?->upazila_id,
                    'child_name' => $still || $context->chance(30) ? null : 'Baby of '.$mother->last_name,
                ], $clerk);
            });
            $n['births']++;
            $still && $n['stillbirths']++;
            $waiting = $i >= $count - 2;       // the last two have not been checked yet
            if ($waiting) {
                continue;
            }
            $context->at($born->addDay(), fn () => $this->births->issue($notification, $manager, 'Father of the child'));
            $stale = $i === 0 || $i === 1;     // issued long ago, never registered: listed for follow-up
            if (! $stale && $context->chance(75) && $born->addDays(12)->isBefore($context->to)) {
                $context->at($born->addDays(mt_rand(4, 12)), fn () => $this->births->register($notification->refresh(), sprintf('%04d%013d', 2026, mt_rand(1000000000000, 9999999999999)), $clerk));
                $n['registered']++;
            }
            if ($i === 3) {
                // a wrong detail found after issue: cancelled with a reason and written again
                $context->at($born->addDays(2), fn () => $this->births->cancel($notification->refresh(), 'The weight was written wrongly; a new notification was made.', $manager));
                $context->at($born->addDays(2)->addHour(), fn () => $this->births->draft($mother, ['born_at' => $born, 'sex' => $notification->sex, 'weight_g' => 3100, 'gestation_weeks' => 39, 'place_of_birth' => 'this_hospital', 'delivery_type' => 'normal'], $clerk));
                $n['births']++;
            }
        }

        return $n;
    }

    /** @return array<string,int> */
    private function deaths(HistoryContext $context, $clerk, $manager, Collection $doctors, $police): array
    {
        $n = ['deaths' => 0, 'issued' => 0, 'police' => 0];
        $dead = Patient::query()->where('organization_id', $context->organization->id)->whereNotNull('deceased_at')->orderBy('deceased_at')->get();
        foreach ($dead->values() as $i => $patient) {
            $died = CarbonImmutable::parse($patient->deceased_at);
            if ($i >= $dead->count() - 2 && $dead->count() > 3) {
                continue;     // the two most recent deaths have no certificate yet
            }
            $accident = $i === 0 || $i === 1;
            $causes = self::CAUSES[$i % count(self::CAUSES)];
            $cert = null;
            $context->at($died->addHours(4), function () use (&$cert, $patient, $died, $accident, $causes, $clerk) {
                $cert = $this->deaths->draft($patient, [
                    'died_at' => $died, 'manner' => $accident ? 'accident' : 'natural', 'place_of_death' => 'this_hospital',
                    'cause_a' => $accident ? 'Severe head injury' : $causes[0], 'interval_a' => $accident ? 'Hours' : '2 days', 'cause_b' => $accident ? 'Road traffic accident' : $causes[1], 'interval_b' => $accident ? 'Hours' : '1 week',
                    'cause_c' => $accident ? null : $causes[2], 'interval_c' => $accident || ! $causes[2] ? null : 'Years', 'underlying_icd10' => $accident ? 'V89.2' : null, 'autopsy_done' => $accident,
                ], $clerk);
            });
            $n['deaths']++;
            $doctor = $doctors->values()[$i % max(1, $doctors->count())];
            $context->at($died->addHours(6), fn () => $this->deaths->certify($cert->refresh(), $doctor, 'A-'.mt_rand(10000, 99999)));
            if ($accident) {
                $n['police']++;
                $case = $cert->refresh()->medicolegalCase;
                $context->at($died->addHours(8), fn () => $this->cases->informPolice($case->refresh(), ['police_station' => $context->pick(['Savar Model Thana', 'Ashulia Thana', 'Keraniganj Model Thana']), 'gd_number' => 'GD-'.mt_rand(100, 999), 'officer_name' => 'SI Rahman', 'officer_contact' => '01700000000'], $police));
                if ($i === 0) {
                    // the first is closed after a post-mortem, and its certificate is then given to the family
                    $context->at($died->addDay(), fn () => $this->cases->requestPostmortem($case->refresh(), $police));
                    $context->at($died->addDays(2), fn () => $this->cases->recordPostmortem($case->refresh(), $police));
                    $context->at($died->addDays(3), fn () => $this->cases->close($case->refresh(), 'Post-mortem done and the body handed over on the police order.', $police));
                } else {
                    continue;     // the second is still with the police: its certificate is held
                }
            }
            $context->at($died->addDays($accident ? 4 : 1), function () use ($cert, $manager, $patient) {
                $this->deaths->issue($cert->refresh(), $manager, 'Family of '.$patient->last_name, 'Son or daughter', 'nid', (string) mt_rand(1000000000, 9999999999));
            });
            $n['issued']++;
        }

        return $n;
    }

    /** @return array<string,int> */
    private function livingCases(HistoryContext $context, $police): array
    {
        $patients = Patient::query()->where('organization_id', $context->organization->id)->whereNull('deceased_at')->orderBy('id')->limit(40)->get()->values();
        $n = ['police' => 0];
        foreach ([['accident', 10], ['assault', 25], ['burn', 40], ['accident', 60], ['poisoning', 90]] as $i => [$kind, $daysAgo]) {
            $patient = $patients[$i] ?? null;
            if (! $patient) {
                break;
            }
            $at = $context->to->subDays($daysAgo)->setTime(mt_rand(1, 22), 15);
            $case = null;
            $context->at($at, function () use (&$case, $patient, $kind, $at, $police) {
                $case = $this->cases->register($patient, ['kind' => $kind, 'brought_at' => $at, 'brought_by' => 'Bystanders', 'description' => 'Brought to the emergency department with injuries; the details were taken by the duty doctor.', 'injuries' => 'Injuries recorded on the emergency chart.'], $police);
            });
            $n['police']++;
            if ($i === 4) {
                continue;     // not told in time: raised on the dashboard
            }
            try {
                $context->at($at->addHours(3), fn () => $this->cases->informPolice($case->refresh(), ['police_station' => 'Local thana', 'gd_number' => 'GD-'.mt_rand(100, 999)], $police));
                $i < 3 && $context->at($at->addDays(2), fn () => $this->cases->close($case->refresh(), 'The police have taken the statement and the medical report.', $police));
            } catch (Throwable) {
                // a case left as it is
            }
        }

        return $n;
    }
}
