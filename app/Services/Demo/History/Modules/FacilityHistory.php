<?php

namespace App\Services\Demo\History\Modules;

use App\Models\Facility\FacAmbulance;
use App\Models\Facility\FacAsset;
use App\Models\Facility\FacBatchItem;
use App\Models\Facility\FacCleaningTask;
use App\Models\Facility\FacDietType;
use App\Models\Facility\FacInstrumentSet;
use App\Models\Facility\FacLinenType;
use App\Models\Facility\FacMortuaryCase;
use App\Models\Facility\FacMortuaryUnit;
use App\Models\Facility\FacSterilisationBatch;
use App\Models\Facility\FacVisitorPass;
use App\Models\Facility\FacWasteManifest;
use App\Models\Facility\FacWorkOrder;
use App\Models\User;
use App\Services\Demo\History\DemoHistoryModule;
use App\Services\Demo\History\HistoryContext;
use App\Services\Facility\AmbulanceService;
use App\Services\Facility\AssetService;
use App\Services\Facility\CssdService;
use App\Services\Facility\DietService;
use App\Services\Facility\FacilityCheck;
use App\Services\Facility\FacilityInstaller;
use App\Services\Facility\HousekeepingService;
use App\Services\Facility\LinenService;
use App\Services\Facility\MaintenanceService;
use App\Services\Facility\MortuaryService;
use App\Services\Facility\VisitorService;
use App\Services\Facility\WasteService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Facility and support services, six months. The equipment register with plans, calibrations and breakdowns (repairs of high-risk equipment checked by a second person, one failed calibration); cleaning tasks from the
 * beds that were released, worked through checklists, with audits; sterile batches released by a second person, packs tied to operations and one recalled; linen cycling through wards and laundry; biomedical waste weighed and
 * collected in manifests; diets for every stay and meal lists for the last weeks; the ambulance fleet and its trips; deaths received by two people and released by two; visitors, attendants and the gate.
 */
class FacilityHistory implements DemoHistoryModule
{
    private const ASSETS = [
        ['Ventilator', 'biomedical', 'high', 6, 'ICU'], ['Infusion pump', 'biomedical', 'high', 10, 'Wards'], ['Patient monitor', 'biomedical', 'high', 8, 'ICU / OT'], ['Defibrillator', 'biomedical', 'high', 3, 'ED / ICU'],
        ['Autoclave', 'biomedical', 'medium', 2, 'CSSD'], ['ECG machine', 'biomedical', 'medium', 3, 'OPD / ED'], ['Suction machine', 'biomedical', 'medium', 6, 'Wards'], ['X-ray unit', 'biomedical', 'high', 2, 'Radiology'],
        ['Wheelchair', 'furniture', 'low', 8, 'Wards'], ['Computer', 'it', 'low', 6, 'Front desk'], ['Generator', 'plant', 'medium', 2, 'Plant room'], ['Air conditioner', 'plant', 'low', 6, 'OT / ICU'],
    ];

    private const FAULTS = ['The alarm sounds all the time and cannot be silenced.', 'It does not switch on, even with a new cable.', 'The reading looks wrong compared with another machine.', 'It makes a grinding noise when it runs.', 'The screen flickers and goes blank.', 'It leaks fluid from the bottom.', 'The battery lasts only a few minutes.'];

    public function __construct(
        private readonly FacilityInstaller $installer, private readonly AssetService $assets, private readonly MaintenanceService $maintenance, private readonly HousekeepingService $housekeeping, private readonly CssdService $cssd,
        private readonly LinenService $linen, private readonly WasteService $waste, private readonly DietService $diet, private readonly AmbulanceService $ambulances, private readonly MortuaryService $mortuary,
        private readonly VisitorService $visitors, private readonly FacilityCheck $check,
    ) {}

    public function name(): string
    {
        return 'Facility and support services';
    }

    public function module(): string
    {
        return 'facility';
    }

    public function run(HistoryContext $context): string
    {
        $org = $context->organization->id;
        $manager = $context->staff('facility_manager')->first();
        $engineer = $context->staff('biomedical_engineer')->first();
        $checker = $context->staff('facility_manager')->first();
        $nurses = collect(['staff_nurse', 'senior_staff_nurse', 'icu_nurse', 'ward_manager'])->flatMap(fn ($r) => $context->staff($r))->unique('id')->values();
        $summary = [];

        $context->at($this->frac($context, 0.0), fn () => $this->installer->install($org));
        $summary[] = $this->equipment($context, $org, $engineer, $checker, $nurses);
        $summary[] = $this->cleaning($context, $org);
        $summary[] = $this->sterile($context, $org, $manager);
        $summary[] = $this->linenAndWaste($context, $org, $manager);
        $summary[] = $this->diets($context, $org);
        $summary[] = $this->transport($context, $org, $manager);
        $summary[] = $this->mortuaryRound($context, $org);
        $summary[] = $this->visitorsAndGate($context, $org);
        $context->at($context->to->setTime(19, 45), fn () => $this->check->run($org));

        return implode('; ', $summary);
    }

    private function frac(HistoryContext $context, float $fraction, int $plusDays = 0): CarbonImmutable
    {
        $total = (int) $context->from->diffInDays($context->to);

        return $context->from->addDays((int) floor($total * $fraction) + $plusDays)->min($context->to->subDay())->setTime(9, 0);
    }

    // ---- equipment ---------------------------------------------------------------------------------------------------------------------------------------------------------

    private function equipment(HistoryContext $context, int $org, User $engineer, User $checker, Collection $nurses): string
    {
        $assets = collect();
        $n = 0;
        foreach (self::ASSETS as [$name, $category, $risk, $count, $place]) {
            for ($i = 1; $i <= $count; $i++) {
                $at = $this->frac($context, 0.0);
                $asset = $context->at($at, fn () => $this->assets->register($org, ['name' => $name.' '.$i, 'category' => $category, 'risk_class' => $risk, 'location' => $place, 'manufacturer' => $context->pick(['Philips', 'Mindray', 'GE Healthcare', 'Drager', 'Local assembler']),
                    'model' => strtoupper($context->pick(['X', 'M', 'P'])).mt_rand(100, 990), 'serial_no' => 'SN'.mt_rand(100000, 999999), 'supplier_name' => $context->pick(['MediSupply Ltd', 'Dhaka Biomedical', 'Eastern Equipment']),
                    'purchased_on' => $at->subMonths(mt_rand(3, 60))->toDateString(), 'cost' => mt_rand(8, 900) * 1000, 'warranty_until' => $at->addMonths(mt_rand(-6, 24))->toDateString(), 'branch_id' => $context->branch->id], $engineer));
                if ($category !== 'furniture' && $category !== 'it') {
                    $context->at($at, fn () => $this->maintenance->plan($asset, 'preventive', $name.' service', $context->pick([90, 90, 180]), $at->addDays(mt_rand(5, 85))->toDateString()));
                }
                if ($risk === 'high') {
                    $context->at($at, fn () => $this->maintenance->plan($asset, 'calibration', $name.' calibration', 180, $at->addDays(mt_rand(20, 170))->toDateString()));
                }
                $assets->push($asset);
                $n++;
            }
        }
        $orders = 0;
        $calibrations = 0;
        $failed = 0;
        $downtime = 0;
        // each day: plans that fall due open orders (Mondays); breakdowns now and then; open orders are worked
        foreach ($context->days() as $day) {
            $at = $day->setTime(7, 0);
            if ($day->isMonday() || $day->isSaturday()) {
                $orders += $context->at($at, fn () => $this->maintenance->openDue($org));
                $this->workOrders($context, $org, $day, $engineer, $checker, $calibrations, $failed);
            }
            if ($context->chance(26)) {
                $asset = $assets->filter(fn ($a) => $a->status !== 'retired')->random();
                $by = $nurses->isEmpty() ? $engineer : $context->pick($nurses);
                $when = $day->setTime(mt_rand(8, 18), mt_rand(0, 59));
                $asset = $asset->fresh();
                if ($asset->status === 'in_service' || $asset->status === 'under_maintenance') {
                    $context->at($when, fn () => $this->maintenance->reportBreakdown($asset, $by, $context->pick(self::FAULTS), $asset->risk_class === 'high' ? 'urgent' : $context->pick(['normal', 'urgent']), $asset->risk_class !== 'low' || $context->chance(50)));
                    $orders++;
                }
            }
            if ($day->isWednesday()) {
                $this->repairs($context, $day, $engineer, $checker, $downtime);
            }
        }
        $this->repairs($context, $context->to->addDay(), $engineer, $checker, $downtime, true);
        $context->at($context->to->setTime(19, 0), fn () => $this->maintenance->alertDue($org));
        $down = FacAsset::query()->where('organization_id', $org)->whereIn('status', ['out_of_service', 'under_maintenance'])->count();

        return sprintf('%d pieces of equipment registered, %d work orders (%d calibrations, %d failed), %d still down', $n, FacWorkOrder::query()->where('organization_id', $org)->count(), $calibrations, $failed, $down);
    }

    /** Plan-made orders (preventive and calibration) are done a few days after they are opened. */
    private function workOrders(HistoryContext $context, int $org, CarbonImmutable $day, User $engineer, User $checker, int &$calibrations, int &$failed): void
    {
        $due = FacWorkOrder::query()->with('asset')->where('organization_id', $org)->whereIn('kind', ['preventive', 'calibration'])->where('status', 'open')->where('reported_at', '<=', $day->subDays(2)->setTime(23, 0))->get();
        foreach ($due as $wo) {
            if (! $context->chance(85) || $wo->asset === null || $wo->asset->status === 'retired') {
                continue;
            }
            $at = $day->setTime(11, 0);
            try {
                $context->at($at, function () use ($wo, $engineer, $checker, $day, &$calibrations, &$failed) {
                    $this->maintenance->start($wo, $engineer);
                    if ($wo->kind === 'calibration') {
                        $fail = mt_rand(1, 100) <= 6;
                        $this->maintenance->calibrate($wo->asset->fresh(), $engineer, $fail ? 'fail' : 'pass', $day->toDateString(), null, 'CAL-'.mt_rand(10000, 99999), 'Metrology Services Ltd', $wo->fresh(), $fail ? 'Reading was outside tolerance.' : null);
                        $calibrations++;
                        $fail && $failed++;
                    }
                    $this->maintenance->complete($wo->fresh(), $engineer, $wo->kind === 'calibration' ? 'Calibrated against the reference standard; certificate filed.' : 'Serviced as per the plan, filters and seals checked, safety test passed.');
                    $fresh = $wo->fresh();
                    if ($fresh->status === 'done' && $wo->asset->risk_class === 'high') {
                        $this->maintenance->verify($fresh, $checker);
                    }
                });
            } catch (Throwable) {
                // an order that cannot be finished yet stays open
            }
        }
    }

    /** Breakdowns are repaired within a few days; high-risk repairs are checked the next day. The newest are left open. */
    private function repairs(HistoryContext $context, CarbonImmutable $day, User $engineer, User $checker, int &$downtime, bool $final = false): void
    {
        $open = FacWorkOrder::query()->with('asset')->whereIn('kind', ['breakdown'])->whereIn('status', ['open', 'in_progress', 'waiting_parts', 'done'])->orderBy('reported_at')->get();
        foreach ($open as $wo) {
            $age = (int) floor(abs($wo->reported_at->diffInDays($day)));
            if ($wo->status === 'done') {
                if ($wo->asset && $wo->asset->risk_class === 'high' && $age >= 3 && (! $final || $age > 4)) {
                    try {
                        $context->at($day->setTime(10, 0), fn () => $this->maintenance->verify($wo->fresh(), $checker));
                    } catch (Throwable) {
                    }
                }

                continue;
            }
            if ($age < 2 || ($final && $age < 6) || (! $final && $context->chance(40))) {
                continue;
            }
            try {
                $at = $day->setTime(15, 0);
                $context->at($at, function () use ($wo, $engineer, $context) {
                    $this->maintenance->start($wo, $engineer);
                    if ($context->chance(45)) {
                        $this->maintenance->addPart($wo->fresh(), $context->pick(['Power supply board', 'Display cable', 'Sensor probe', 'Fuse set', 'Battery pack', 'Pressure valve']), 1, mt_rand(8, 120) * 100);
                    }
                    $this->maintenance->complete($wo->fresh(), $engineer, 'Found and replaced the faulty part; tested for an hour under load.');
                });
            } catch (Throwable) {
            }
        }
    }

    // ---- cleaning ----------------------------------------------------------------------------------------------------------------------------------------------------------

    private function cleaning(HistoryContext $context, int $org): string
    {
        $cleaners = $context->staff('housekeeper');
        $supervisor = $context->staff('housekeeping_supervisor')->first();
        $tasks = FacCleaningTask::query()->where('organization_id', $org)->orderBy('requested_at')->get();
        $done = 0;
        foreach ($tasks as $k => $t) {
            if ($t->requested_at->gt($context->to->subHours(30))) {
                continue; // the newest are still waiting
            }
            $cleaner = $cleaners->isEmpty() ? $supervisor : $context->pick($cleaners);
            $minutes = $context->chance(70) ? mt_rand(20, 44) : mt_rand(46, 130);
            try {
                $context->at(CarbonImmutable::instance($t->requested_at)->addMinutes($minutes), function () use ($t, $cleaner) {
                    $this->housekeeping->start($t, $cleaner);
                    $this->housekeeping->complete($t->fresh(), $cleaner, array_keys((array) $t->checklist));
                });
                $done++;
            } catch (Throwable) {
            }
        }
        $weeks = 0;
        foreach ($context->days() as $day) {
            if (! $day->isThursday()) {
                continue;
            }
            foreach (['Ward '.mt_rand(1, 6), $context->pick(['Operation theatre', 'ICU', 'Emergency', 'Laboratory'])] as $area) {
                $items = (array) config('facility.housekeeping.checklists.room');
                $rate = 0.7 + 0.22 * ($context->from->diffInDays($day) / max(1, $context->from->diffInDays($context->to)));
                $results = collect($items)->map(fn ($i) => ['item' => $i, 'ok' => mt_rand(0, 100) / 100 < $rate])->all();
                $context->at($day->setTime(11, 0), fn () => $this->housekeeping->audit($org, $area, $results, $supervisor, $day->toDateString(), $context->branch->id));
            }
            $weeks++;
        }
        $theatre = $context->at($this->frac($context, 0.5), fn () => $this->housekeeping->request($org, 'theatre', 'Operation theatre 1 — terminal clean', $supervisor, $context->branch->id));
        $context->at($this->frac($context, 0.5)->addHours(3), function () use ($theatre, $supervisor) {
            $this->housekeeping->start($theatre, $supervisor);
            $this->housekeeping->complete($theatre->fresh(), $supervisor, array_keys((array) $theatre->checklist));
        });
        $turn = FacCleaningTask::query()->where('organization_id', $org)->whereNotNull('turnaround_minutes')->avg('turnaround_minutes');

        return sprintf('%d cleaning tasks finished (average %s minutes), %d audit weeks', $done + 1, $turn === null ? '—' : round((float) $turn), $weeks);
    }

    // ---- sterile services ----------------------------------------------------------------------------------------------------------------------------------------------------

    private function sterile(HistoryContext $context, int $org, User $manager): string
    {
        $tech = $context->staff('cssd_technician')->first();
        $sets = FacInstrumentSet::query()->where('organization_id', $org)->get();
        $machines = FacAsset::query()->where('organization_id', $org)->where('name', 'like', 'Autoclave%')->get();
        $batches = 0;
        foreach ($context->days() as $day) {
            if ($day->isFriday() || ! $context->chance(60)) {
                continue;
            }
            $machine = $machines->isEmpty() ? null : $context->pick($machines)->fresh();
            $packs = [];
            for ($i = 0, $count = mt_rand(3, 7); $i < $count; $i++) {
                $packs[] = $context->pick($sets)->id;
            }
            $method = $context->chance(85) ? 'steam' : $context->pick(['eto', 'plasma']);
            try {
                $at = $day->setTime(8, 30);
                $batch = $context->at($at, fn () => $this->cssd->load($org, $method, $packs, $tech, $machine && $machine->status === 'in_service' ? $machine : null, $context->branch->id));
                $context->at($at->addMinutes(15), fn () => $this->cssd->start($batch));
                $fail = mt_rand(1, 100) <= 3;
                $context->at($at->addMinutes($method === 'steam' ? 75 : 240), fn () => $this->cssd->finish($batch->fresh(), $fail ? 'fail' : 'pass', $method === 'steam' ? 134 : null, $method === 'steam' ? 205 : null, $method === 'steam' ? 18 : 180));
                if (! $fail) {
                    $context->at($at->addMinutes($method === 'steam' ? 100 : 270), fn () => $this->cssd->release($batch->fresh(), $manager));
                }
                $batches++;
            } catch (Throwable) {
            }
        }
        // the packs go to operations, oldest first
        $used = 0;
        $packs = FacBatchItem::query()->with('batch')->where('status', 'packed')->whereHas('batch', fn ($q) => $q->where('organization_id', $org)->where('status', 'released'))->orderBy('id')->get();
        $surgeries = DB::table('ot_surgeries')->where('organization_id', $org)->whereIn('status', ['procedure_completed', 'recovery', 'transferred', 'closed'])->whereNotNull('completed_at')->orderBy('completed_at')->get(['id', 'completed_at']);
        $cursor = 0;
        foreach ($surgeries as $s) {
            $when = CarbonImmutable::parse($s->completed_at)->subHour();
            for ($k = 0; $k < mt_rand(1, 2); $k++) {
                while ($cursor < $packs->count() && $packs[$cursor]->batch->released_at->gt($when)) {
                    $cursor++;
                    break;
                }
                $pack = $packs->get($cursor);
                if (! $pack || $pack->batch->released_at->gt($when)) {
                    continue;
                }
                $cursor++;
                try {
                    $context->at($when, fn () => $this->cssd->issue($pack->fresh(), (int) $s->id, $tech));
                    $used++;
                } catch (Throwable) {
                }
            }
        }
        // one released batch is recalled, with the operations it touched
        $recalled = 0;
        $target = FacSterilisationBatch::query()->where('organization_id', $org)->where('status', 'released')->whereHas('items', fn ($q) => $q->where('status', 'issued'))->orderBy('id')->skip(2)->first();
        if ($target) {
            $traced = $context->at($context->to->setTime(11, 0), fn () => $this->cssd->recall($target, $manager, 'The chemical indicator lot used for this load was withdrawn by the supplier.'));
            $recalled = count($traced);
        }

        return sprintf('%d sterile batches (%d packs used in operations, 1 batch recalled after %d used)', $batches, $used, $recalled);
    }

    // ---- linen and waste ---------------------------------------------------------------------------------------------------------------------------------------------------

    private function linenAndWaste(HistoryContext $context, int $org, User $manager): string
    {
        $keeper = $context->staff('housekeeping_supervisor')->first();
        $types = FacLinenType::query()->where('organization_id', $org)->get();
        $context->at($this->frac($context, 0.0), function () use ($types, $keeper) {
            foreach ($types as $t) {
                $this->linen->move($t, 'receive_new', $t->par_level * 2, $keeper, 'Linen store');
            }
        });
        $moves = 0;
        foreach ($context->days() as $day) {
            foreach ($types as $t) {
                $at = $day->setTime(8, 0);
                $b = $this->linen->balance($t);
                $issue = min($b['clean_store'], (int) floor($t->par_level * mt_rand(20, 35) / 100));
                $steps = [];
                $issue > 0 && $steps[] = ['issue', $issue, 'Ward '.mt_rand(1, 6)];
                $collect = min($b['on_wards'] + $issue, (int) floor($t->par_level * mt_rand(20, 35) / 100));
                $collect > 0 && $steps[] = ['collect_soiled', $collect, 'Ward '.mt_rand(1, 6)];
                $send = min($b['soiled'] + $collect, $day->isWeekday() ? (int) floor($t->par_level * mt_rand(20, 40) / 100) : 0);
                $send > 4 && $steps[] = ['send_laundry', $send, 'Laundry van'];
                $back = min($b['at_laundry'], (int) floor($t->par_level * mt_rand(15, 30) / 100));
                $back > 0 && $day->isWeekday() && $steps[] = ['receive_clean', $back, 'Laundry van'];
                foreach ($steps as $k => [$movement, $qty, $where]) {
                    try {
                        $context->at($at->addMinutes($k * 20), fn () => $this->linen->move($t->fresh(), $movement, $qty, $keeper, $where, $day->toDateString(), null, $context->branch->id));
                        $moves++;
                    } catch (Throwable) {
                    }
                }
                if ($day->isSunday() && $context->chance(40) && $this->linen->balance($t)['soiled'] > 2) {
                    $context->at($at->addHours(3), fn () => $this->linen->move($t->fresh(), 'condemn', 2, $keeper, 'Torn or stained beyond repair', $day->toDateString()));
                    $moves++;
                }
            }
        }
        // waste: weighed daily, collected weekly
        $records = 0;
        foreach ($context->days() as $day) {
            foreach (['infectious' => [8, 22], 'sharps' => [1, 4], 'pathological' => [0, 3], 'pharmaceutical' => [0, 2], 'general' => [30, 70]] as $category => [$lo, $hi]) {
                $kg = mt_rand($lo * 10, max($lo * 10 + 1, $hi * 10)) / 10;
                if ($kg < 0.5) {
                    continue;
                }
                $context->at($day->setTime(17, 0), fn () => $this->waste->record($org, $category, $context->pick(['Ward 1', 'Ward 2', 'ICU', 'Operation theatre', 'Laboratory', 'Emergency', 'Kitchen']), $kg, $keeper, $day->toDateString(), $context->branch->id));
                $records++;
            }
            if ($day->isSaturday() && $day->lt($context->to->subDays(3))) {
                $context->at($day->setTime(12, 0), function () use ($org, $day, $manager, $context) {
                    $m = $this->waste->manifest($org, 'Green Disposal Ltd', 'DHAKA METRO-DA-'.mt_rand(1000, 9999), $day->toDateString(), $context->branch->id);
                    $this->waste->handOver($m, $manager);
                    $day->lt($context->to->subDays(10)) && $this->waste->confirm($m->fresh(), 'DC-'.$day->format('Ymd').'-'.mt_rand(100, 999), $day->addDay()->toDateString());
                });
            }
        }
        $manifests = FacWasteManifest::query()->where('organization_id', $org)->count();

        return sprintf('%d linen movements, %d waste weighings in %d manifests', $moves, $records, $manifests);
    }

    // ---- diets --------------------------------------------------------------------------------------------------------------------------------------------------------------

    private function diets(HistoryContext $context, int $org): string
    {
        $dietitian = $context->staff('dietitian')->first();
        $kitchen = $context->staff('kitchen_staff')->first();
        $types = FacDietType::query()->where('organization_id', $org)->get()->keyBy('code');
        $weights = array_merge(array_fill(0, 45, 'NORMAL'), array_fill(0, 18, 'DIABETIC'), array_fill(0, 10, 'LOWSALT'), array_fill(0, 7, 'SOFT'), array_fill(0, 6, 'LIQUID'), array_fill(0, 5, 'RENAL'), array_fill(0, 5, 'PAEDIATRIC'), array_fill(0, 4, 'HIGHPROT'));
        $orders = 0;
        $admissions = DB::table('ipd_admissions as a')->leftJoin('ipd_bed_allocations as al', 'al.admission_id', '=', 'a.id')->leftJoin('ipd_beds as b', 'b.id', '=', 'al.bed_id')->leftJoin('ipd_rooms as r', 'r.id', '=', 'b.room_id')
            ->where('a.organization_id', $org)->groupBy('a.id', 'a.admitted_at', 'a.actual_discharge_date')->orderBy('a.admitted_at')->get(['a.id', 'a.admitted_at', 'a.actual_discharge_date', DB::raw('max(r.ward_id) as ward_id')]);
        foreach ($admissions as $a) {
            $at = CarbonImmutable::parse($a->admitted_at)->addHours(2);
            if ($at->gt($context->to)) {
                continue;
            }
            $code = $context->pick($weights);
            $texture = ['LIQUID' => 'liquid', 'SOFT' => 'soft'][$code] ?? ($context->chance(4) ? 'nil_by_mouth' : 'normal');
            try {
                $order = $context->at($at, fn () => $this->diet->order($org, (int) $a->id, $types[$code], ['texture' => $texture, 'ward_id' => $a->ward_id, 'allergies' => $context->chance(8) ? $context->pick(['Peanuts', 'Shellfish', 'Eggs', 'Milk']) : null], $dietitian));
                $orders++;
                if ($context->chance(30) && ($a->actual_discharge_date === null || CarbonImmutable::parse($a->actual_discharge_date)->gt($at->addDays(2)))) {
                    $change = $types[$context->pick(['NORMAL', 'SOFT', 'DIABETIC'])];
                    $context->at($at->addDays(2), fn () => $this->diet->order($org, (int) $a->id, $change, ['texture' => 'normal', 'ward_id' => $a->ward_id], $dietitian));
                    $orders++;
                }
            } catch (Throwable) {
            }
        }
        // meal lists for the last two weeks, all served
        $lists = 0;
        $wards = DB::table('ipd_wards')->where('organization_id', $org)->pluck('id');
        foreach ($context->days() as $day) {
            if ($day->lt($context->to->subDays(13))) {
                continue;
            }
            foreach (['breakfast', 'lunch', 'dinner'] as $meal) {
                foreach ($wards->take(3) as $ward) {
                    try {
                        $at = $day->setTime(['breakfast' => 7, 'lunch' => 12, 'dinner' => 19][$meal], 0);
                        $context->at($at, function () use ($org, $day, $meal, $ward, $kitchen, $context, &$lists) {
                            $list = $this->diet->mealList($org, $day->toDateString(), $meal, (int) $ward, $context->branch->id);
                            if ($list->trays()->count() === 0) {
                                return;
                            }
                            foreach ($list->trays()->with('order')->get() as $tray) {
                                if ($tray->status === 'planned') {
                                    $this->diet->serve($tray, $context->chance(92) ? 'served' : 'refused', $context->chance(10) ? 'Said the food was too spicy' : null);
                                }
                            }
                            $this->diet->closeList($list->fresh(), $kitchen);
                            $lists++;
                        });
                    } catch (Throwable) {
                    }
                }
            }
        }

        return sprintf('%d diet orders, %d meal lists served', $orders, $lists);
    }

    // ---- ambulances ---------------------------------------------------------------------------------------------------------------------------------------------------------

    private function transport(HistoryContext $context, int $org, User $manager): string
    {
        $dispatcher = $context->staff('ambulance_dispatcher')->first();
        $fleet = collect([['DHAKA METRO-CHA-11-2041', 'advanced', 48200], ['DHAKA METRO-CHA-11-3377', 'basic', 22500], ['DHAKA METRO-CHA-13-0908', 'mortuary', 61000]])
            ->map(fn ($v) => $context->at($this->frac($context, 0.0), fn () => $this->ambulances->add($org, $v[0], $v[1], null, $v[2])));
        $drivers = ['Rahim Mia', 'Karim Sheikh', 'Jalal Uddin', 'Habib Khan'];
        $patientIds = $context->patients()->pluck('id')->all();
        $edVisits = DB::table('ed_visits')->where('organization_id', $org)->where('arrival_mode', 'ambulance')->orderBy('arrival_at')->get(['id', 'patient_id', 'arrival_at']);
        $trips = 0;
        $cancelled = 0;
        $n = 0;
        foreach ($context->days() as $day) {
            $count = mt_rand(0, 3);
            $edToday = $edVisits->filter(fn ($v) => CarbonImmutable::parse($v->arrival_at)->isSameDay($day));
            foreach ($edToday as $v) {
                $this->trip($context, $org, $dispatcher, $fleet->whereIn('type', ['advanced', 'basic'])->values(), $drivers, $day, CarbonImmutable::parse($v->arrival_at)->subMinutes(mt_rand(20, 40)), ['kind' => 'emergency', 'from_place' => $context->pick(['Mirpur 10', 'Uttara Sector 7', 'Mohakhali', 'Gulshan 2', 'Dhanmondi 27', 'Badda']), 'to_place' => 'Emergency department', 'patient_id' => $v->patient_id, 'ed_visit_id' => $v->id], $cancelled);
                $trips++;
            }
            for ($i = 0; $i < $count; $i++) {
                $kind = $context->pick(['transfer', 'transfer', 'discharge', 'discharge', 'referral', 'emergency']);
                $at = $day->setTime(mt_rand(8, 20), mt_rand(0, 59));
                $this->trip($context, $org, $dispatcher, $fleet->whereIn('type', ['advanced', 'basic'])->values(), $drivers, $day, $at, ['kind' => $kind, 'from_place' => $context->pick(['Ward 2', 'ICU', 'Emergency', 'Ward 4']), 'to_place' => $context->pick(['Patient\'s home, Mirpur', 'City Heart Hospital', 'Patient\'s home, Savar', 'Dhaka Medical College', 'Patient\'s home, Tongi']),
                    'patient_id' => $patientIds ? $context->pick($patientIds) : null], $cancelled);
                $trips++;
            }
            if ($day->isSunday()) {
                $v = $fleet->first()->fresh();
                $context->at($day->setTime(18, 0), fn () => $this->ambulances->fuel($v, mt_rand(40, 70), mt_rand(40, 70) * 118, $v->odometer_km, $day->toDateString()));
            }
            if ($day->day === 14) {
                $b = $fleet[1]->fresh();
                $b->status === 'available' && $context->at($day->setTime(9, 0), fn () => $this->ambulances->setMaintenance($b, true));
                $context->at($day->addDays(2)->min($context->to)->setTime(9, 0), fn () => $this->ambulances->setMaintenance($b->fresh(), false));
            }
            $n++;
        }
        $avg = $this->ambulances->averageResponseMinutes($org, $context->from->toDateString(), $context->to->toDateString());

        return sprintf('%d ambulance trips (%d cancelled; average %s minutes to leave)', $trips, $cancelled, $avg ?? '—');
    }

    /** @param Collection<int,FacAmbulance> $fleet */
    private function trip(HistoryContext $context, int $org, User $dispatcher, Collection $fleet, array $drivers, CarbonImmutable $day, CarbonImmutable $at, array $d, int &$cancelled): void
    {
        try {
            $trip = $context->at($at, fn () => $this->ambulances->request($org, $d + ['branch_id' => $context->branch->id], $dispatcher));
            $vehicle = $fleet->first(fn ($a) => $a->fresh()->status === 'available');
            if (! $vehicle || $context->chance(3)) {
                $context->at($at->addMinutes(5), fn () => $this->ambulances->cancel($trip, 'The family made their own arrangements.'));
                $cancelled++;

                return;
            }
            $leave = $at->addMinutes(mt_rand(2, $d['kind'] === 'emergency' ? 9 : 25));
            $context->at($leave, fn () => $this->ambulances->dispatch($trip, $vehicle->fresh(), $context->pick($drivers)));
            $context->at($leave->addMinutes(mt_rand(12, 35)), fn () => $this->ambulances->arrive($trip->fresh()));
            $km = mt_rand(4, 45);
            $context->at($leave->addMinutes(mt_rand(40, 110)), fn () => $this->ambulances->complete($trip->fresh(), $vehicle->fresh()->odometer_km + $km));
        } catch (Throwable) {
        }
    }

    // ---- mortuary ----------------------------------------------------------------------------------------------------------------------------------------------------------------

    private function mortuaryRound(HistoryContext $context, int $org): string
    {
        $attendant = $context->staff('mortuary_attendant')->first();
        $verifier = $context->staff('staff_nurse')->first();
        $releaser = $context->staff('facility_manager')->first();
        $unit = FacMortuaryUnit::query()->where('organization_id', $org)->first();
        $context->at($context->to->setTime(8, 0), fn () => $this->mortuary->sweep($org));
        $received = $released = 0;
        foreach (FacMortuaryCase::query()->where('organization_id', $org)->orderBy('died_at')->get() as $k => $case) {
            $at = CarbonImmutable::instance($case->died_at)->addHours(mt_rand(1, 3));
            if ($at->gt($context->to) || $case->status !== 'pending') {
                continue;
            }
            $police = $k % 7 === 3;
            try {
                $context->at($at, fn () => $this->mortuary->receive($case, $attendant, $verifier, 'T-'.mt_rand(1000, 9999), $unit, $police));
                $received++;
                $when = $at->addDay()->setTime(11, 0);
                if ($police) {
                    $context->at($when, fn () => $this->mortuary->clearPolice($case->fresh(), $attendant));
                }
                if ($when->lte($context->to->subDay()) && $k % 5 !== 4) {
                    $context->at($when->addHours(2), fn () => $this->mortuary->requestRelease($case->fresh(), $attendant, $context->pick(['Abdul Karim', 'Rahima Begum', 'Shamsul Haque', 'Nasrin Akter']), $context->pick(['Son', 'Brother', 'Wife', 'Daughter']), 'national_id', (string) mt_rand(1000000000, 9999999999)));
                    $context->at($when->addHours(3), fn () => $this->mortuary->authoriseRelease($case->fresh(), $releaser));
                    $released++;
                }
            } catch (Throwable) {
            }
        }

        return sprintf('%d bodies received (%d released by two people)', $received, $released);
    }

    // ---- visitors ------------------------------------------------------------------------------------------------------------------------------------------------------------------

    private function visitorsAndGate(HistoryContext $context, int $org): string
    {
        $desk = $context->staff('reception_gate_officer')->first();
        $guard = $context->staff('security_guard')->first();
        $supervisor = $context->staff('facility_manager')->first();
        $names = ['Abdul Karim', 'Rahima Begum', 'Shamsul Haque', 'Nasrin Akter', 'Mizanur Rahman', 'Sultana Razia', 'Faruk Hossain', 'Jesmin Ara', 'Habibur Rahman', 'Taslima Khatun', 'Monir Hossain', 'Salma Begum'];
        $context->at($this->frac($context, 0.05), function () use ($org, $supervisor) {
            $this->visitors->block($org, 'Mr. Kamal Ahmed', '01711000999', 'Threatened a nurse in the casualty corridor and was removed by security.', $supervisor);
            $this->visitors->block($org, 'Ms. Rupa Sen', null, 'Took photographs of patients without permission.', $supervisor);
        });
        $admissions = DB::table('ipd_admissions')->where('organization_id', $org)->orderBy('admitted_at')->get(['id', 'admitted_at', 'actual_discharge_date']);
        $passes = $entries = 0;
        foreach ($admissions as $k => $a) {
            if ($k % 2 !== 0) {
                continue;
            }
            $in = CarbonImmutable::parse($a->admitted_at)->addHours(3);
            $out = $a->actual_discharge_date ? CarbonImmutable::parse($a->actual_discharge_date) : $context->to;
            if ($in->gt($context->to)) {
                continue;
            }
            // an attendant stays with some patients
            if ($context->chance(45)) {
                try {
                    $att = $context->at($in, fn () => $this->visitors->issue($org, (int) $a->id, ['kind' => 'attendant', 'visitor_name' => $context->pick($names), 'phone' => '01'.mt_rand(711111111, 999999999), 'relation' => $context->pick(['Wife', 'Mother', 'Son', 'Husband']), 'id_type' => 'national_id', 'id_number' => (string) mt_rand(1000000000, 9999999999)], $desk));
                    $context->at($in->addMinutes(10), fn () => $this->visitors->enter($att, $guard, 'Main gate'));
                    $passes++;
                    $entries++;
                } catch (Throwable) {
                }
            }
            // visitors on a few evenings of the stay (17:00 Dhaka = 11:00 UTC)
            for ($d = $in->startOfDay()->addDay(); $d->lt($out) && $d->lte($context->to); $d = $d->addDays(mt_rand(1, 3))) {
                $arrive = $d->setTime(11, mt_rand(0, 40));
                if ($arrive->lt($in) || $arrive->gt($out)) {
                    continue;
                }
                try {
                    $pass = $context->at($arrive->subMinutes(5), fn () => $this->visitors->issue($org, (int) $a->id, ['kind' => 'visitor', 'visitor_name' => $context->pick($names), 'phone' => '01'.mt_rand(711111111, 999999999), 'relation' => $context->pick(['Brother', 'Friend', 'Neighbour', 'Daughter'])], $desk));
                    $context->at($arrive, fn () => $this->visitors->enter($pass, $guard, 'Visitors gate'));
                    $context->at($arrive->addMinutes(mt_rand(20, 50)), fn () => $this->visitors->leave($pass->fresh(), $guard, 'Visitors gate'));
                    $passes++;
                    $entries++;
                } catch (Throwable) {
                }
            }
        }
        // a few refusals: outside visiting hours, and a blocked person
        $a = $admissions->first(fn ($x) => $x->actual_discharge_date !== null && CarbonImmutable::parse($x->actual_discharge_date)->gt(CarbonImmutable::parse($x->admitted_at)->addDays(4)));
        if ($a) {
            $late = CarbonImmutable::parse($a->admitted_at)->addDay()->setTime(8, 0); // 14:00 Dhaka: not visiting hours
            for ($i = 0; $i < 2; $i++) {
                try {
                    $pass = $context->at($late->addDays($i), fn () => $this->visitors->issue($org, (int) $a->id, ['kind' => 'visitor', 'visitor_name' => $context->pick($names), 'phone' => '01'.mt_rand(711111111, 999999999)], $desk));
                    $context->at($late->addDays($i)->addMinutes(5), fn () => $this->visitors->enter($pass, $guard, 'Visitors gate'));
                } catch (Throwable) {
                    // refused at the gate: the log keeps it
                }
            }
        }
        $context->at($context->to->setTime(19, 30), fn () => $this->visitors->expireEnded($org));

        return sprintf('%d visitor and attendant passes, %d entries, %d refused at the gate', FacVisitorPass::query()->where('organization_id', $org)->count(), $entries, DB::table('fac_gate_logs')->where('organization_id', $org)->where('outcome', 'refused')->count());
    }
}
