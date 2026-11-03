<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Crm\CrmComplaint;
use App\Models\Hr\HrEmployee;
use App\Models\Patient;
use App\Models\Quality\QaAction;
use App\Models\Quality\QaCommittee;
use App\Models\Quality\QaIncident;
use App\Models\Quality\QaIncidentReporter;
use App\Models\Quality\QaIndicator;
use App\Models\Quality\QaMortalityReview;
use App\Models\Quality\QaOutbreak;
use App\Models\Quality\QaPrivilegeCatalog;
use App\Models\Quality\QaStandard;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Quality\AccreditationService;
use App\Services\Quality\ActionService;
use App\Services\Quality\ClinicalAuditService;
use App\Services\Quality\ComplaintLinkService;
use App\Services\Quality\ComplianceService;
use App\Services\Quality\DocumentService;
use App\Services\Quality\IncidentService;
use App\Services\Quality\IncidentSweep;
use App\Services\Quality\IndicatorService;
use App\Services\Quality\InfectionService;
use App\Services\Quality\InvestigationService;
use App\Services\Quality\MeetingService;
use App\Services\Quality\MortalityService;
use App\Services\Quality\PrivilegeService;
use App\Services\Quality\QualityCheck;
use App\Services\Quality\QualityInstaller;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Quality, safety and compliance, six months. Staff report incidents and near misses (a quarter of them without a name); the quality officer reviews them, serious ones are investigated and accepted by a second
 * person, actions are done and checked by someone else, older ones are closed and a few are left open and late. Events in other modules are swept in weekly. Deaths are reviewed (a few late), infections are
 * recorded with an outbreak that is later closed, isolation and hand-hygiene rounds run, audits are done with gaps turned into actions, documents are approved and read, privileges are granted by a second person,
 * the licence calendar and an accreditation checklist are worked through, committees and the morbidity and mortality meeting meet and are minuted, and some complaints are raised as incidents.
 */
class QualityHistory implements DemoHistoryModule
{
    private const CATEGORIES = ['fall' => 12, 'medication' => 24, 'pressure_injury' => 6, 'procedure' => 8, 'infection' => 6, 'equipment' => 9, 'identification' => 6, 'communication' => 10, 'diet' => 3, 'behaviour' => 4, 'security' => 3, 'fire_safety' => 2, 'documentation' => 7];

    private const TEXT = [
        'fall' => ['A patient slipped on a wet floor near the washroom and was helped up; no injury was found.', 'An elderly patient got out of bed without help at night and fell beside the bed.', 'A visitor tripped on a loose floor mat at the ward entrance.'],
        'medication' => ['A dose was drawn up at the wrong strength and noticed before it was given.', 'Two similar-looking medicine labels were swapped on the trolley.', 'A medicine was given an hour after the time written on the chart.', 'An allergy was not on the drug chart when the medicine was prepared.'],
        'pressure_injury' => ['A new pressure sore was found on the heel of a patient who had been in bed for six days.', 'Skin redness over the sacrum was noticed at the evening round and the turning chart was started.'],
        'procedure' => ['The site marking was missing when the patient arrived at the theatre door.', 'A swab count was repeated because the first count did not match.', 'A procedure was delayed because the consent form was incomplete.'],
        'infection' => ['A drip site became red and swollen after three days and was changed.', 'A urinary catheter was left in longer than the plan said.'],
        'equipment' => ['The infusion pump alarm failed to sound when the line was blocked.', 'A suction machine did not work when it was needed and a second one was fetched.', 'The weighing scale on the ward read two kilograms too high.'],
        'identification' => ['A blood sample was labelled for the wrong bed and caught at the laboratory.', 'Two patients with the same name were on the same ward and their files were nearly swapped.'],
        'communication' => ['A change to the plan of care was not passed on at the shift handover.', 'A relative was not told about a delay and complained at the desk.', 'The result of an urgent test was told by phone but not written down.'],
        'diet' => ['A patient who needed a soft diet was served a normal meal.', 'A diabetic patient was served a sweet drink with the evening meal.'],
        'behaviour' => ['A relative shouted at a nurse after a long wait.', 'A patient became aggressive in the waiting area and security was called.'],
        'security' => ['An unknown person was found in the ward after visiting hours.', 'A bag was taken from the staff room.'],
        'fire_safety' => ['A fire exit was found blocked by a trolley.', 'A smoke alarm was missing its battery.'],
        'documentation' => ['A discharge summary was missing the follow-up date.', 'A chart entry was made on the wrong patient and corrected.', 'A consent form was filed without a signature date.'],
    ];

    private const GAPS = ['Staff did not always say the patient name aloud.', 'The checklist was done but not signed.', 'Only some beds had the hand rub bottle in reach.'];

    public function __construct(
        private readonly QualityInstaller $installer, private readonly IncidentService $incidents, private readonly IncidentSweep $sweep, private readonly InvestigationService $investigations, private readonly ActionService $actions,
        private readonly InfectionService $infection, private readonly IndicatorService $indicators, private readonly ClinicalAuditService $audits, private readonly MortalityService $mortality, private readonly DocumentService $documents,
        private readonly PrivilegeService $privileges, private readonly ComplianceService $compliance, private readonly AccreditationService $accreditation, private readonly MeetingService $meetings,
        private readonly ComplaintLinkService $links, private readonly QualityCheck $check,
    ) {}

    public function name(): string
    {
        return 'Quality, safety and compliance';
    }

    public function module(): string
    {
        return 'quality';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $officer = $context->staff('quality_officer')->first();
        $manager = $context->staff('quality_manager')->first();
        $director = $context->staff('medical_director')->first();
        $ic = $context->staff('infection_control_officer')->first();
        $auditor = $context->staff('clinical_auditor')->first();
        $cred = $context->staff('credentialing_officer')->first();
        $reporters = collect(['staff_nurse', 'senior_staff_nurse', 'doctor', 'pharmacist', 'ward_manager', 'lab_technician', 'ot_nurse', 'icu_nurse'])->flatMap(fn ($r) => $context->staff($r))->unique('id')->values();
        $owners = collect(['ward_manager', 'nursing_supervisor', 'pharmacy_manager', 'lab_manager', 'ot_manager', 'icu_manager', 'department_manager', 'inventory_manager'])->flatMap(fn ($r) => $context->staff($r))->unique('id')->values();
        $patientIds = $context->patients()->pluck('id')->all();
        $stats = ['incidents' => 0, 'closed' => 0, 'actions' => 0, 'investigations' => 0];

        $context->at($context->from->setTime(7, 0), fn () => $this->installer->install($org));

        // ---- incidents: reported through the days, swept in weekly, then worked
        $created = [];
        $queued = [];
        $lastId = (int) QaIncident::query()->max('id');
        foreach ($context->days() as $day) {
            $count = $context->chance(72) ? ($context->chance(25) ? 2 : 1) : 0;
            for ($n = 0; $n < $count; $n++) {
                $at = $context->workTime($day, mt_rand(0, 500));
                $made = $this->report($context, $org, $at, $reporters, $patientIds);
                $created[] = [$made, $at];
                $queued[$made->id] = true;
            }
            if ($day->isSunday()) {
                $at = $day->setTime(18, 0);
                $context->at($at, function () use ($org, &$created, &$queued, &$lastId, $at) {
                    $this->sweep->run($org, 9);
                    $this->mortality->sweep($org);
                    foreach (QaIncident::query()->where('organization_id', $org)->where('id', '>', $lastId)->orderBy('id')->get() as $i) {
                        if (! isset($queued[$i->id])) {
                            $queued[$i->id] = true;
                            $created[] = [$i, $at];
                        }
                    }
                    $lastId = (int) QaIncident::query()->max('id');
                });
            }
        }
        $work = collect([$officer, $manager])->filter()->values();
        foreach ($created as [$incident, $at]) {
            $this->progress($context, $incident->fresh(), $at, $work->first(), $director, $auditor ?? $manager, $owners, $reporters, $stats);
        }

        // ---- deaths: reviewed by the medical director (a few late)
        $reviews = QaMortalityReview::query()->where('organization_id', $org)->orderBy('died_at')->get();
        foreach ($reviews as $n => $r) {
            $age = $this->span($r->died_at, $context->to);
            if ($age < 6 || $n % 3 === 2) {
                continue; // recent ones and an occasional late one wait
            }
            $at = CarbonImmutable::instance($r->died_at)->addDays(mt_rand(2, min(26, $age - 1)))->setTime(11, 0);
            $classification = $context->pick(array_merge(array_fill(0, 10, 'expected'), array_fill(0, 5, 'unexpected_unavoidable'), array_fill(0, 3, 'potentially_preventable'), ['preventable']));
            $context->at($at, function () use ($r, $director, $classification) {
                $this->mortality->assign($r, $director, $director);
                $this->mortality->review($r->fresh(), $director, $classification, 'The records were read from arrival to death. The treatment followed the usual plan; the timing of the senior review and the observations were checked against the chart.', $classification === 'expected' ? null : 'Check that the senior doctor is called early when the observations worsen.');
            });
        }

        // ---- infection control
        $inf = $this->infectionControl($context, $org, $ic ?? $manager);

        // ---- audits, documents, privileges, the calendar, accreditation, meetings, complaints
        $aud = $this->clinicalAudits($context, $org, $auditor ?? $officer, $owners, $manager);
        $docs = $this->documentControl($context, $org, $officer, $director, $manager);
        $priv = $this->privilegeList($context, $org, $cred ?? $manager, $director);
        $cal = $this->calendar($context, $org, $manager);
        $acc = $this->accreditationRound($context, $org, $manager, $owners);
        $mtg = $this->meetingRound($context, $org, $manager, $director, $owners);
        $cmp = $this->complaints($context, $org, $officer, $owners);

        // ---- indicators and the daily round
        $context->at($context->to->setTime(19, 30), function () use ($org) {
            foreach (['falls_per_1000' => 2.5, 'hand_hygiene' => 85] as $code => $target) {
                $i = QaIndicator::query()->whereNull('organization_id')->where('code', $code)->first();
                $i && $this->indicators->setTarget($org, $i, $target);
            }
            $this->indicators->computeRecent($org, 7);
            $this->check->run($org);
        });
        $open = QaIncident::query()->where('organization_id', $org)->where('status', '!=', 'closed')->count();
        $late = QaAction::query()->where('organization_id', $org)->whereIn('status', ['open', 'in_progress'])->where('due_on', '<', $context->to->toDateString())->count();
        $deaths = QaMortalityReview::query()->where('organization_id', $org)->count();
        $waiting = QaMortalityReview::query()->where('organization_id', $org)->where('status', 'pending')->count();

        return sprintf('%d incidents reported or found (%d closed, %d open), %d investigations, %d actions (%d past due); %d death reviews (%d waiting); %s; %s; %s; %s; %s; %s; %s',
            QaIncident::query()->where('organization_id', $org)->count(), $stats['closed'], $open, $stats['investigations'], QaAction::query()->where('organization_id', $org)->count(), $late, $deaths, $waiting, $inf, $aud, $docs, $priv, $cal, $acc.'; '.$mtg, $cmp);
    }

    /** A point in the history, as a fraction of its length (so one month and six months both read sensibly), plus some days. */
    private function frac(HistoryContext $context, float $fraction, int $plusDays = 0): CarbonImmutable
    {
        $total = (int) $context->from->diffInDays($context->to);

        return $context->from->addDays((int) floor($total * $fraction) + $plusDays)->min($context->to->subDay())->setTime(10, 0);
    }

    private function span(\DateTimeInterface $a, \DateTimeInterface $b): int
    {
        return (int) floor(abs(CarbonImmutable::instance($a)->diffInDays(CarbonImmutable::instance($b))));
    }

    // ---- incidents ---------------------------------------------------------------------------------------------------------------------------------------------------------

    private function report(HistoryContext $context, int $org, CarbonImmutable $at, Collection $reporters, array $patientIds): QaIncident
    {
        $category = $context->pick(collect(self::CATEGORIES)->flatMap(fn ($w, $c) => array_fill(0, $w, $c)));
        $kind = $context->chance(30) ? 'near_miss' : ($context->chance(8) ? 'adverse_event' : 'incident');
        $harm = $kind === 'near_miss' ? 'none' : $context->pick(array_merge(array_fill(0, 38, 'none'), array_fill(0, 40, 'minor'), array_fill(0, 15, 'moderate'), array_fill(0, 5, 'major'), ['catastrophic']));
        $by = $reporters->isEmpty() ? $context->actor : $context->pick($reporters);
        $patient = in_array($category, (array) config('quality.incident.patient_categories'), true) && $context->chance(70) && $patientIds ? $context->pick($patientIds) : null;

        return $context->at($at, fn () => $this->incidents->report($org, ['kind' => $kind, 'category' => $category, 'harm' => $harm, 'occurred_at' => $at->setTimezone('Asia/Dhaka')->subMinutes(mt_rand(5, 180))->format('Y-m-d H:i'),
            'description' => $context->pick(self::TEXT[$category]), 'immediate_action' => $context->chance(60) ? 'The senior nurse was told and the area was checked.' : null, 'location' => 'Ward '.mt_rand(1, 6), 'branch_id' => $context->branch->id,
            'patient_id' => $patient, 'anonymous' => $context->chance(25)], $by));
    }

    /** @param array{incidents:int,closed:int,actions:int,investigations:int} $stats */
    private function progress(HistoryContext $context, QaIncident $i, CarbonImmutable $at, User $officer, User $director, User $second, Collection $owners, Collection $reporters, array &$stats): void
    {
        $age = $this->span($at, $context->to);
        if ($age < 2 && $context->chance(60)) {
            return; // very recent: not looked at yet
        }
        $t = $at->addDay()->setTime(10, 0);
        $owner = $owners->isEmpty() ? $officer : $context->pick($owners);
        // a named reporter sometimes corrects the kind of event
        if (! $i->is_anonymous && $age > 3 && $context->chance(5)) {
            $reporter = QaIncidentReporter::query()->where('incident_id', $i->id)->first();
            $reporter && $context->at($at->addHours(5), fn () => $this->incidents->amend($i, User::query()->find($reporter->user_id), 'location', 'Ward '.mt_rand(1, 6).' (corrected)', 'I wrote the wrong ward.'));
        }
        $review = $i->harm === 'minor' && $context->chance(15) ? 'moderate' : null;
        $i = $context->at($t, fn () => $this->incidents->triage($i, $officer, $review, $owner));
        if ($age < 10 && $context->chance(50)) {
            return;
        }
        if ($this->incidents->needsInvestigation($i)) {
            $lead = $owners->reject(fn ($u) => $u->id === $director->id)->first() ?? $officer;
            $t = $t->addDay();
            $inv = $context->at($t, fn () => $this->investigations->start($i->fresh(), $lead, $context->pick(InvestigationService::METHODS), $officer));
            $stats['investigations']++;
            if ($age < 12) {
                return;
            }
            $t = $t->addDays(4);
            $context->at($t, function () use ($inv, $lead, $director, $t, $context) {
                $this->investigations->save($inv, 'The sequence of events was rebuilt from the chart and from talking to the staff on duty. The task was done under time pressure and the usual check was not built into the routine.',
                    [['category' => 'process', 'text' => 'The check is not part of the written routine'], ['category' => 'staffing', 'text' => 'One nurse covered two areas at the time'], ['category' => 'communication', 'text' => 'The change was passed on only by word of mouth']]);
                $this->investigations->submit($inv->fresh(), $lead);
                $context->at($t->addDays(2), fn () => $this->investigations->review($inv->fresh(), $director, true, null));
            });
            $t = $t->addDays(2);
        }
        $actionCount = $this->incidents->needsInvestigation($i) ? 2 : ($context->chance(55) ? 1 : 0);
        $made = [];
        for ($n = 0; $n < $actionCount; $n++) {
            $due = $t->addDays(mt_rand(10, 28));
            $a = $context->at($t->addDay(), fn () => $this->actions->create($i->organization_id, ['source_type' => 'incident', 'source_id' => $i->id, 'action_type' => $context->pick(['corrective', 'preventive', 'improvement']),
                'description' => $context->pick(['Add the check to the written routine and put it on the chart', 'Brief all shifts at handover and put a reminder on the wall', 'Replace or service the equipment and keep the record', 'Change the layout so the safe step is the easy one', 'Train the team and test with a short quiz']),
                'owner_id' => $owner->id, 'due_on' => $due->toDateString(), 'branch_id' => $i->branch_id], $officer));
            $stats['actions']++;
            if ($due->lte($context->to->subDays(2))) {
                $doneAt = $context->chance(80) ? $due->subDays(mt_rand(1, 6)) : $due->addDays(mt_rand(2, 9));
                $doneAt = $doneAt->gt($context->to) ? $context->to : $doneAt->max($t->addDays(2));
                $context->at($doneAt->setTime(15, 0), fn () => $this->actions->complete($a->fresh(), $owner, 'Done as described; the new routine is in use on all shifts.'));
                $verifyAt = $doneAt->addDays(3);
                if ($verifyAt->lte($context->to)) {
                    $checker = $second->id !== $owner->id ? $second : $director;
                    $context->at($verifyAt->setTime(11, 0), fn () => $this->actions->verify($a->fresh(), $checker, $context->chance(90), 'Seen in use on two visits.'));
                }
            }
            $made[] = $a;
        }
        // older incidents are closed (a few are left open on purpose)
        $closeAt = $t->addDays(mt_rand(18, 32));
        $openActions = QaAction::query()->where(['source_type' => 'incident', 'source_id' => $i->id])->whereIn('status', ['open', 'in_progress', 'done'])->count();
        if ($closeAt->lte($context->to) && $openActions === 0 && ! $context->chance(6)) {
            try {
                $context->at($closeAt->setTime(14, 0), fn () => $this->incidents->close($i->fresh(), $officer, 'Reviewed with the team. The cause was a gap in the routine and it has been closed.'));
                $stats['closed']++;
            } catch (Throwable) {
                // an investigation not yet accepted keeps it open
            }
        }
    }

    // ---- infection control ----------------------------------------------------------------------------------------------------------------------------------------------

    private function infectionControl(HistoryContext $context, int $org, User $ic): string
    {
        $rows = DB::table('ipd_admissions as a')->join('ipd_bed_allocations as al', 'al.admission_id', '=', 'a.id')->join('ipd_beds as b', 'b.id', '=', 'al.bed_id')->join('ipd_rooms as r', 'r.id', '=', 'b.room_id')
            ->where('a.organization_id', $org)->whereNotNull('r.ward_id')->orderBy('a.admitted_at')->groupBy('a.id', 'a.patient_id', 'a.admitted_at', 'a.actual_discharge_date', 'r.ward_id')
            ->get(['a.id', 'a.patient_id', 'a.admitted_at', 'a.actual_discharge_date', 'r.ward_id'])->map(fn ($r) => (object) ['id' => (int) $r->id, 'patient_id' => (int) $r->patient_id, 'ward_id' => (int) $r->ward_id, 'in' => CarbonImmutable::parse($r->admitted_at),
                'out' => $r->actual_discharge_date ? CarbonImmutable::parse($r->actual_discharge_date) : $context->to])->filter(fn ($r) => $this->span($r->in, $r->out) >= 4);
        $cases = 0;
        $onsetFor = fn ($r, int $days) => $r->in->addDays($days)->setTime(15, 0);

        // an outbreak: three MRSA cases on the busiest ward within ten days
        $outbreak = null;
        $ward = $rows->groupBy('ward_id')->sortByDesc(fn ($g) => $g->count())->keys()->first();
        if ($ward && $rows->where('ward_id', $ward)->count() >= 3) {
            $group = $rows->where('ward_id', $ward)->filter(fn ($r) => $r->in->between($this->frac($context, 0.30), $this->frac($context, 0.55)))->sortBy('in')->values();
            $picked = collect();
            foreach ($group as $r) {
                if ($picked->isEmpty() || $this->span($r->in, $picked->first()->in) <= 8) {
                    $picked->push($r);
                }
                if ($picked->count() === 3) {
                    break;
                }
            }
            if ($picked->count() === 3) {
                foreach ($picked as $r) {
                    $at = $onsetFor($r, 4);
                    $case = $context->at($at, fn () => $this->infection->reportCase($org, ['patient_id' => $r->patient_id, 'admission_id' => $r->id, 'ward_id' => $r->ward_id, 'hai_type' => 'mrsa', 'organism' => 'MRSA', 'onset_on' => $at->toDateString(), 'branch_id' => $context->branch->id], $ic));
                    $context->at($at->addDay(), fn () => $this->infection->decide($case->fresh(), $ic, true));
                    $cases++;
                }
                $outbreak = QaOutbreak::query()->where('organization_id', $org)->first();
                $outbreak && $context->at($picked->last()->in->addDays(26)->min($context->to), fn () => $this->infection->closeOutbreak($outbreak->fresh(), $ic, 'Screening and cleaning done, contact precautions kept for 3 weeks, no new case.'));
            }
        }
        // other infections spread over the months
        foreach ($rows->values() as $n => $r) {
            if ($n % 11 !== 4) {
                continue;
            }
            $day = $context->pick([3, 4, 5, 6]);
            $at = $onsetFor($r, min($day, max(3, $this->span($r->in, $r->out) - 1)));
            if ($at->gt($context->to)) {
                continue;
            }
            $type = $context->pick(['cauti', 'cauti', 'clabsi', 'ssi', 'ssi', 'vap', 'cdiff']);
            $case = $context->at($at, fn () => $this->infection->reportCase($org, ['patient_id' => $r->patient_id, 'admission_id' => $r->id, 'ward_id' => $r->ward_id, 'hai_type' => $type,
                'organism' => $context->pick(['E. coli', 'Klebsiella', 'Staph aureus', 'Pseudomonas']), 'device' => ['cauti' => 'urinary catheter', 'clabsi' => 'central line', 'vap' => 'ventilator'][$type] ?? null, 'onset_on' => $at->toDateString(), 'branch_id' => $context->branch->id], $ic));
            $context->at($at->addDay(), fn () => $this->infection->decide($case->fresh(), $ic, $context->chance(75)));
            $cases++;
        }
        // isolation for a share of the patients, ended after some days
        $isolations = 0;
        foreach ($rows->values() as $n => $r) {
            if ($n % 6 !== 2) {
                continue;
            }
            $start = $r->in->addDay()->setTime(9, 0);
            $patient = Patient::query()->find($r->patient_id);
            if (! $patient) {
                continue;
            }
            try {
                $iso = $context->at($start, fn () => $this->infection->isolate($org, $patient, $context->pick(['contact', 'contact', 'droplet', 'airborne', 'enteric']), $context->pick(['Resistant organism found', 'Suspected tuberculosis', 'Diarrhoea under investigation', 'Chickenpox contact']), $ic, $r->id));
            } catch (Throwable) {
                continue; // the same precaution is already in place for this patient
            }
            $end = $start->addDays(mt_rand(3, 8));
            if ($end->lte($context->to) && $end->lte($r->out->addDay()) && $context->chance(92)) {
                $context->at($end, fn () => $this->infection->endIsolation($iso->fresh(), $ic));
            }
            $isolations++;
        }
        // hand-hygiene rounds every week, improving
        $wards = DB::table('ipd_wards')->where('organization_id', $org)->get(['id', 'name']);
        $weeks = 0;
        foreach ($context->days() as $day) {
            if (! $day->isWednesday() || $wards->isEmpty()) {
                continue;
            }
            $progress = $context->from->diffInDays($day) / max(1, $context->from->diffInDays($context->to));
            foreach (['nurse', 'doctor'] as $role) {
                $ward = $context->pick($wards);
                $seen = mt_rand(30, 60);
                $ok = (int) round($seen * min(0.97, (0.66 + 0.2 * $progress + (mt_rand(-5, 5) / 100)) - ($role === 'doctor' ? 0.06 : 0)));
                $context->at($day->setTime(14, 0), fn () => $this->infection->auditHandHygiene($org, ['ward_id' => $ward->id, 'ward_name' => $ward->name, 'staff_role' => $role, 'moments_observed' => $seen, 'moments_compliant' => $ok, 'audited_on' => $day->toDateString(), 'branch_id' => $context->branch->id], $ic));
            }
            $weeks++;
        }

        return sprintf('%d infections recorded (%d outbreak%s), %d isolations, %d hand-hygiene weeks', $cases, QaOutbreak::query()->where('organization_id', $org)->count(), QaOutbreak::query()->where('organization_id', $org)->count() === 1 ? '' : 's', $isolations, $weeks);
    }

    // ---- clinical audits -------------------------------------------------------------------------------------------------------------------------------------------------

    private function clinicalAudits(HistoryContext $context, int $org, User $auditor, Collection $owners, User $manager): string
    {
        $set = [
            [1, 'Surgical safety checklist use', 'All three phases are signed for every operation', [['Sign-in done', 30, 0], ['Time-out done', 28, 2], ['Sign-out done', 21, 9]], true],
            [2, 'Patient identification at medicine rounds', 'Two identifiers are checked before each dose', [['Wristband checked', 40, 3], ['Name asked aloud', 26, 17], ['Allergy checked', 38, 5]], true],
            [4, 'Hand hygiene before aseptic procedures', 'Hands cleaned before every aseptic procedure', [['Before the procedure', 33, 7], ['After the procedure', 36, 4]], true],
            [5, 'Drug chart completeness', 'Every chart has allergies, weight and a signature', [['Allergy recorded', 41, 4], ['Signed and dated', 38, 7]], false],
        ];
        $done = 0;
        $raised = 0;
        foreach ($set as [$month, $title, $standard, $criteria, $complete]) {
            $at = $this->frac($context, ($month - 1) / 6, mt_rand(0, 3));
            $audit = $context->at($at, fn () => $this->audits->create($org, ['title' => $title, 'standard' => $standard, 'audited_on' => $at->toDateString(), 'sample_size' => 45, 'branch_id' => $context->branch->id], $auditor));
            $context->at($at->addDays(2)->min($context->to), fn () => $this->audits->record($audit, collect($criteria)->map(fn ($c) => ['criterion' => $c[0], 'met' => $c[1], 'not_met' => $c[2]])->all()));
            if (! $complete) {
                continue;
            }
            $context->at($at->addDays(4)->min($context->to), function () use ($audit, $auditor, $owners, $manager, $context, &$raised, $at) {
                $done = $this->audits->complete($audit->fresh(), $auditor, 'The audit found good practice on most criteria and clear gaps on the ones below.');
                foreach ($this->audits->gaps($done->fresh('findings')) as $gap) {
                    $owner = $owners->isEmpty() ? $manager : $context->pick($owners);
                    $this->actions->create($done->organization_id, ['source_type' => 'audit', 'source_id' => $done->id, 'action_type' => 'corrective', 'description' => 'Improve: '.$gap['criterion'], 'owner_id' => $owner->id,
                        'due_on' => $at->addDays(40)->toDateString(), 'branch_id' => $done->branch_id], $auditor);
                    $raised++;
                }
            });
            $done++;
        }

        return sprintf('%d clinical audits (%d completed, %d gap actions)', count($set), $done, $raised);
    }

    // ---- documents --------------------------------------------------------------------------------------------------------------------------------------------------------------

    private function documentControl(HistoryContext $context, int $org, User $author, User $approver, User $manager): string
    {
        $docs = [
            ['IPC-01', 'Hand hygiene policy', 'policy', 'all', 'Clean your hands at the five moments: before touching a patient, before a clean or aseptic procedure, after body fluid exposure, after touching a patient, after touching the patient\'s surroundings.'],
            ['MED-01', 'Safe medicine administration', 'sop', 'all', 'Check the patient with two identifiers, the medicine, the dose, the route and the time against the chart. Record the dose straight after giving it.'],
            ['SUR-01', 'Surgical safety checklist', 'protocol', 'role:ot_nurse', 'Use the three-phase checklist for every operation: sign in before anaesthesia, time-out before incision, sign-out before the patient leaves the theatre.'],
            ['FIRE-01', 'Fire and evacuation plan', 'policy', 'all', 'On hearing the alarm, close doors, move patients away from the fire, use the nearest safe exit and meet at the assembly point. Never use the lifts.'],
            ['INC-01', 'Reporting incidents and near misses', 'policy', 'all', 'Report any event that harmed or could have harmed a patient, visitor or member of staff, including near misses. You may report without your name. Reports are for learning, not blame.'],
            ['BWM-01', 'Biomedical waste segregation', 'sop', 'role:staff_nurse', 'Separate waste at the point of use into the coloured bins. Sharps go in the puncture-proof container. Record the weight at each collection.'],
            ['TRF-01', 'Blood transfusion bedside check', 'protocol', 'role:senior_staff_nurse', 'Two staff check the patient, the bag and the compatibility label together at the bedside before every transfusion and record the check.'],
            ['ISO-01', 'Isolation precautions', 'guideline', 'role:ward_manager', 'Place the isolation sign on the door, tell the patient and family why, use the right protective equipment and end the precautions only on the infection control officer\'s advice.'],
        ];
        $approved = 0;
        $acks = 0;
        foreach ($docs as $n => [$code, $title, $category, $audience, $body]) {
            $at = $this->frac($context, 0.02 + $n * 0.03);
            $doc = $context->at($at, fn () => $this->documents->create($org, ['code' => $code, 'title' => $title, 'category' => $category, 'body' => $body, 'review_months' => $n === 0 ? 6 : 12, 'owner_id' => $author->id], $author));
            $v1 = $doc->versions()->first();
            $context->at($at->addDay(), fn () => $this->documents->submit($v1, $author));
            $context->at($at->addDays(3), fn () => $this->documents->decide($v1->fresh(), $approver, true, 'Clear and usable.', $at->addDays(4)->toDateString()));
            $approved++;
            $audiences = $audience === 'all' ? [['all', '']] : [['role', substr($audience, 5)]];
            foreach ($audiences as [$kind, $value]) {
                $context->at($at->addDays(3), fn () => $this->documents->assign($doc->fresh(), $kind, $value));
            }
            if ($n === 1 || $n === 4) {
                $mid = $this->frac($context, 0.5, $n);
                $v2 = $context->at($mid, fn () => $this->documents->newVersion($doc->fresh(), $body.' Updated after review by the committee: the checklist is now kept in the folder at the nurses\' station.', 'Added where the checklist is kept', $author));
                $context->at($mid->addDay(), fn () => $this->documents->submit($v2, $author));
                $context->at($mid->addDays(2), fn () => $this->documents->decide($v2->fresh(), $approver, true, 'Approved.', $mid->addDays(3)->toDateString()));
            }
            if ($n === 6) {
                $v2 = $context->at($context->to->subDays(3), fn () => $this->documents->newVersion($doc->fresh(), $body.' The second check is now done by a nurse from the blood bank team at night.', 'Night-time second check', $author));
                $context->at($context->to->subDays(2), fn () => $this->documents->submit($v2, $author)); // waiting for approval
            }
            $doc = $doc->fresh();
            $readers = $this->documents->audience($doc)->take(70);
            foreach ($readers as $uid) {
                if ($context->chance(72)) {
                    $user = User::query()->find($uid);
                    $user && $context->at($at->addDays(5 + mt_rand(0, 25))->min($context->to)->setTime(mt_rand(8, 17), 0), function () use ($doc, $user, &$acks) {
                        $this->documents->acknowledge($doc->fresh(), $user);
                        $acks++;
                    });
                }
            }
        }

        return sprintf('%d documents approved (2 in a second version, 1 waiting), %d readings', $approved, $acks);
    }

    // ---- privileges, calendar, accreditation ----------------------------------------------------------------------------------------------------------------------------

    private function privilegeList(HistoryContext $context, int $org, User $cred, User $decider): string
    {
        $catalog = QaPrivilegeCatalog::query()->where('organization_id', $org)->get()->keyBy('code');
        $rows = DB::table('hr_credentials as c')->join('hr_employees as e', 'e.id', '=', 'c.employee_id')->where('e.organization_id', $org)->where('c.status', 'active')->where(fn ($q) => $q->whereNull('c.expires_on')->orWhere('c.expires_on', '>', $context->to->toDateString()))
            ->orderBy('e.id')->get(['e.id as employee_id', 'c.credential_type'])->unique(fn ($r) => $r->employee_id.$r->credential_type);
        $granted = $waiting = 0;
        $map = ['bmdc' => ['SURG-GEN', 'ANAES', 'C-SECTION', 'ENDOSCOPY'], 'bnmc' => ['IV-CHEMO']];
        foreach ($rows->take(22) as $n => $r) {
            $code = $context->pick($map[$r->credential_type] ?? ['ICU-VENT']);
            $item = $catalog[$code] ?? null;
            $employee = HrEmployee::query()->find($r->employee_id);
            if (! $item || ! $employee) {
                continue;
            }
            $at = $this->frac($context, 0.05 + $n * 0.025);
            try {
                $p = $context->at($at, fn () => $this->privileges->request($org, $employee, $item, $cred, 'Case log and referee reports attached.'));
                if ($n % 7 === 6) {
                    $waiting++; // a few requests wait for a decision

                    continue;
                }
                $context->at($at->addDays(3), fn () => $this->privileges->decide($p, $decider, true, 'Case log reviewed with the head of department.'));
                $granted++;
                if ($n === 9) {
                    $context->at($at->addDays(40)->min($context->to), fn () => $this->privileges->withdraw($p->fresh(), $decider, false, 'Suspended while a case is reviewed; no finding against the clinician.'));
                }
            } catch (Throwable) {
                // an employee who already holds it, or a licence that lapsed, is skipped
            }
        }

        return sprintf('%d privileges granted, %d waiting', $granted, $waiting);
    }

    private function calendar(HistoryContext $context, int $org, User $owner): string
    {
        $items = [
            ['licence', 'Hospital operating licence', 'Directorate of Health', 12, 200], ['certificate', 'Fire safety certificate', 'Fire Service', 12, 25], ['licence', 'Biomedical waste licence', 'Department of Environment', 12, 70],
            ['licence', 'X-ray and radiation licence', 'Atomic Energy Commission', 24, 400], ['inspection', 'Lift inspection', 'Lift contractor', 6, -9], ['insurance', 'Public liability insurance', 'Insurer', 12, 110], ['licence', 'Pharmacy licence', 'Drug Administration', 12, 45],
        ];
        $n = 0;
        foreach ($items as [$kind, $title, $authority, $months, $dueIn]) {
            $item = $context->at($this->frac($context, 0.0), fn () => $this->compliance->add($org, ['kind' => $kind, 'title' => $title, 'authority' => $authority, 'due_on' => $context->to->addDays($dueIn)->toDateString(), 'interval_months' => $months, 'owner_id' => $owner->id, 'branch_id' => $context->branch->id], $owner));
            if ($n === 1 || $n === 5) {
                $when = $this->frac($context, 0.3);
                $context->at($when, fn () => $this->compliance->complete($item->fresh(), $owner, $when->toDateString(), $context->to->addDays($dueIn)->toDateString()));
            }
            $n++;
        }

        return sprintf('%d items on the licence calendar', $n);
    }

    private function accreditationRound(HistoryContext $context, int $org, User $lead, Collection $owners): string
    {
        $std = QaStandard::query()->where(['organization_id' => $org, 'code' => 'HOSP-BASIC'])->first();
        if (! $std) {
            return 'no checklist';
        }
        $gaps = 0;
        foreach ([['Annual self-assessment', 2, true], ['Mid-year check', 5, false]] as [$name, $month, $complete]) {
            $at = $this->frac($context, ($month - 1) / 6, 2);
            $a = $context->at($at, fn () => $this->accreditation->start($org, $std, $name, $lead, $context->branch->id));
            $results = $a->results()->orderBy('id')->get();
            foreach ($results as $k => $r) {
                if (! $complete && $k >= (int) ($results->count() / 2)) {
                    break;
                }
                $result = $context->pick(array_merge(array_fill(0, 60, 'compliant'), array_fill(0, 20, 'partial'), array_fill(0, 12, 'non_compliant'), array_fill(0, 8, 'na')));
                $r = $context->at($at->addDays(1 + $k % 5)->min($context->to), function () use ($a, $r, $lead, $result, $context) {
                    $this->accreditation->record($a->fresh(), $r->fresh(), $lead, $result, $result === 'na' ? null : ($result === 'compliant' ? 'Seen on the ward round and in the records.' : $context->pick(self::GAPS)));

                    return $r->fresh();
                });
                if ($complete && in_array($result, ['partial', 'non_compliant'], true)) {
                    $owner = $owners->isEmpty() ? $lead : $context->pick($owners);
                    $context->at($at->addDays(8)->min($context->to), fn () => $this->accreditation->gapAction($a->fresh(), $r->fresh('item'), ['owner_id' => $owner->id, 'due_on' => $at->addDays(50)->toDateString()], $lead));
                    $gaps++;
                }
            }
            if ($complete) {
                $context->at($at->addDays(10)->min($context->to), fn () => $this->accreditation->complete($a->fresh(), $lead));
            }
        }

        return sprintf('2 checklist assessments (1 completed, %d gap actions)', $gaps);
    }

    // ---- committees ------------------------------------------------------------------------------------------------------------------------------------------------------

    private function meetingRound(HistoryContext $context, int $org, User $secretary, User $chair, Collection $owners): string
    {
        $attendees = collect([$secretary, $chair])->merge($owners->take(4))->pluck('id')->unique()->values()->all();
        $held = 0;
        foreach (QaCommittee::query()->where('organization_id', $org)->get() as $committee) {
            for ($m = 0; $m < 6; $m++) {
                $start = $this->frac($context, $m / 6)->startOfDay();
                $window = max(5, (int) floor($this->span($context->from, $context->to) / 6));
                $end = $start->addDays($window - 1)->endOfDay();
                $at = $start->addDays(max(2, $window - 2))->setTime(14, 0);
                $future = $at->gt($context->to);
                $meeting = $context->at($future ? $context->to->setTime(9, 0) : $at->subDays(7), fn () => $this->meetings->schedule($committee, $committee->name.' — '.$start->format('d M Y'), $at->setTimezone('Asia/Dhaka')->format('Y-m-d H:i'), 'Review of the month, open actions, new business.', 'Conference room'));
                if ($future) {
                    continue;
                }
                $items = [];
                $context->at($at->subDays(3), function () use ($committee, $meeting, $org, $start, $end, &$items) {
                    if ($committee->kind === 'mm') {
                        foreach (QaMortalityReview::query()->where('organization_id', $org)->whereBetween('died_at', [$start->utc(), $end->utc()])->where('status', 'reviewed')->limit(3)->get() as $r) {
                            $items[] = $this->meetings->addItem($meeting, 'mortality', $r->id);
                        }
                    } elseif ($committee->kind === 'safety') {
                        foreach (QaIncident::query()->where('organization_id', $org)->whereBetween('occurred_at', [$start->utc(), $end->utc()])->whereIn('harm', ['moderate', 'major', 'catastrophic'])->limit(2)->get() as $i) {
                            $items[] = $this->meetings->addItem($meeting, 'incident', $i->id);
                        }
                    }
                    $items[] = $this->meetings->addItem($meeting, 'topic', null, $committee->kind === 'infection' ? 'Hand-hygiene results and outbreak review' : ($committee->kind === 'mm' ? 'Learning points from the month' : 'Open actions and late items'));
                });
                $context->at($at, fn () => $this->meetings->hold($meeting->fresh(), $attendees, $secretary));
                foreach ($items as $k => $item) {
                    $context->at($at->addMinutes(10 + 10 * $k), fn () => $this->meetings->recordItem($meeting->fresh(), $item->fresh(), $secretary, 'The case was presented, the records were read, and the team discussed what could be done differently.',
                        $k === 0 ? 'Agreed to change the routine and report back' : 'Noted; no further action', $k === 0 && $context->chance(60) && $owners->isNotEmpty() ? ['description' => 'Report back to the committee on the change agreed at the meeting', 'owner_id' => $context->pick($owners)->id, 'due_on' => $at->addDays(30)->toDateString()] : null));
                }
                $context->at($at->addHours(2), fn () => $this->meetings->minute($meeting->fresh(), $secretary, 'Present: '.count($attendees).' members. The items on the agenda were discussed and each has an outcome recorded. Actions were given owners and dates.'));
                $held++;
            }
        }

        // the next meeting of each committee is on the calendar
        foreach (QaCommittee::query()->where('organization_id', $org)->get() as $committee) {
            $next = $context->to->addDays(7)->setTime(14, 0);
            $context->at($context->to->setTime(17, 0), fn () => $this->meetings->schedule($committee, $committee->name.' — '.$next->format('d M Y'), $next->setTimezone('Asia/Dhaka')->format('Y-m-d H:i'), 'Review of the month, open actions, new business.', 'Conference room'));
        }

        return sprintf('%d committee meetings minuted, next ones planned', $held);
    }

    private function complaints(HistoryContext $context, int $org, User $officer, Collection $owners): string
    {
        $made = 0;
        foreach (CrmComplaint::query()->where('organization_id', $org)->orderBy('id')->limit(40)->get()->filter(fn ($c, $k) => $k % 6 === 1)->take(6) as $c) {
            try {
                $at = CarbonImmutable::instance($c->created_at)->addDay()->setTime(11, 0);
                if ($at->gt($context->to)) {
                    continue;
                }
                $context->at($at, function () use ($c, $officer) {
                    $i = $this->links->raise($c, $officer, $c->severity === 'high' ? 'minor' : 'none');
                    $this->incidents->triage($i, $officer, null, $officer);
                });
                $made++;
            } catch (Throwable) {
            }
        }

        return sprintf('%d complaints raised as incidents', $made);
    }
}
