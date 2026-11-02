<?php

namespace App\Services\Facility;

use App\Models\Facility\FacAsset;
use App\Models\Facility\FacBatchItem;
use App\Models\Facility\FacInstrumentSet;
use App\Models\Facility\FacSterilisationBatch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sterile services. Sets are packed into a batch, the batch runs, and only a batch whose indicator passed — released by a second person — may be used. Every pack that goes to an operation is tied to it,
 * so a failed or recalled batch can be traced to the patients it touched.
 */
class CssdService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly AssetService $assets, private readonly FacilityAlerts $alerts, private readonly AuditLogger $audit) {}

    public function set(int $organizationId, string $code, string $name, int $instruments = 0): FacInstrumentSet
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen(trim($name)) < 3) {
            throw ValidationException::withMessages(['name' => __('Give the set a code and a name.')]);
        }

        return FacInstrumentSet::query()->updateOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => mb_substr(trim($name), 0, 150), 'instrument_count' => max(0, $instruments), 'active' => true]);
    }

    /** Pack sets into a new batch. @param list<int> $setIds one entry per pack */
    public function load(int $organizationId, string $method, array $setIds, User $operator, ?FacAsset $steriliser = null, ?int $branchId = null): FacSterilisationBatch
    {
        if (! in_array($method, $this->policy->list('cssd.methods'), true)) {
            throw ValidationException::withMessages(['method' => __('Choose the method.')]);
        }
        if ($setIds === []) {
            throw ValidationException::withMessages(['sets' => __('Pack at least one set.')]);
        }
        if ($steriliser && ($steriliser->organization_id !== $organizationId || ! $this->assets->usable($steriliser))) {
            throw ValidationException::withMessages(['asset' => __('That steriliser cannot be used: :r', ['r' => (string) $this->assets->reason($steriliser)])]);
        }

        return DB::transaction(function () use ($organizationId, $method, $setIds, $operator, $steriliser, $branchId) {
            $batch = FacSterilisationBatch::create(['organization_id' => $organizationId, 'branch_id' => $branchId, 'number' => $this->numbers->next('fac.batch', $organizationId, null, prefix: 'STB', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
                'asset_id' => $steriliser?->id, 'method' => $method, 'status' => 'loading', 'operator_id' => $operator->id]);
            foreach (array_values($setIds) as $n => $setId) {
                $set = FacInstrumentSet::query()->where('organization_id', $organizationId)->where('active', true)->find($setId) ?? throw ValidationException::withMessages(['sets' => __('Unknown set.')]);
                FacBatchItem::create(['batch_id' => $batch->id, 'set_id' => $set->id, 'label' => $batch->number.'-'.str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT), 'status' => 'packed']);
            }
            $this->audit->log('CREATE', FacSterilisationBatch::class, $batch->id, null, ['number' => $batch->number, 'packs' => count($setIds)]);

            return $batch;
        });
    }

    public function start(FacSterilisationBatch $batch): FacSterilisationBatch
    {
        $this->mustBe($batch, ['loading']);
        $batch->update(['status' => 'running', 'started_at' => now()]);

        return $batch;
    }

    /** The cycle ended: record what the machine did and what the indicator showed. */
    public function finish(FacSterilisationBatch $batch, string $indicator, ?float $temperature, ?float $pressure, ?int $minutes): FacSterilisationBatch
    {
        $this->mustBe($batch, ['running']);
        if (! in_array($indicator, ['pass', 'fail'], true)) {
            throw ValidationException::withMessages(['indicator' => __('Choose the indicator result.')]);
        }
        $batch->update(['status' => $indicator === 'pass' ? 'passed' : 'failed', 'ended_at' => now(), 'indicator_result' => $indicator, 'temperature_c' => $temperature, 'pressure_kpa' => $pressure, 'minutes' => $minutes]);
        if ($indicator === 'fail') {
            FacBatchItem::query()->where('batch_id', $batch->id)->update(['status' => 'withdrawn']);
            $this->alerts->raise($batch->organization_id, 'warning', __('Sterilisation batch :n failed its indicator.', ['n' => $batch->number]), FacSterilisationBatch::class, $batch->id, 'fac-batch-fail-'.$batch->id, 'facility.cssd.manage', false, $batch->branch_id);
        }
        $this->audit->log('UPDATE', FacSterilisationBatch::class, $batch->id, null, ['indicator' => $indicator]);

        return $batch;
    }

    /** A second person releases a passed batch: the packs get a use-by date. */
    public function release(FacSterilisationBatch $batch, User $by): FacSterilisationBatch
    {
        $this->mustBe($batch, ['passed']);
        if ($this->policy->bool('cssd.second_person_releases', true) && $batch->operator_id === $by->id) {
            throw ValidationException::withMessages(['status' => __('A second person must release the batch.')]);
        }
        $days = (int) (((array) $this->policy->get('cssd.shelf_life_days', []))[$batch->method] ?? 30);

        return DB::transaction(function () use ($batch, $by, $days) {
            $batch->update(['status' => 'released', 'released_by' => $by->id, 'released_at' => now()]);
            FacBatchItem::query()->where('batch_id', $batch->id)->update(['use_by' => today()->addDays($days > 0 ? $days : 30)->toDateString()]);
            $this->audit->log('UPDATE', FacSterilisationBatch::class, $batch->id, null, ['released' => true]);

            return $batch->refresh();
        });
    }

    /** Give a pack to an operation. Only a released, unexpired, unissued pack goes out. */
    public function issue(FacBatchItem $item, int $surgeryId, User $by): FacBatchItem
    {
        $batch = $item->batch;
        $surgery = DB::table('ot_surgeries')->where('organization_id', $batch->organization_id)->where('id', $surgeryId)->first(['id', 'patient_id']);
        if (! $surgery) {
            throw ValidationException::withMessages(['surgery' => __('Unknown operation.')]);
        }
        if ($batch->status !== 'released' || $item->status !== 'packed') {
            throw ValidationException::withMessages(['item' => __('This pack cannot be used: it is not a released, unused pack.')]);
        }
        if ($item->use_by && $item->use_by->lt(today())) {
            throw ValidationException::withMessages(['item' => __('This pack is past its use-by date.')]);
        }
        $item->update(['status' => 'issued', 'surgery_id' => $surgery->id, 'patient_id' => $surgery->patient_id, 'issued_at' => now(), 'issued_by' => $by->id]);
        $this->audit->log('UPDATE', FacBatchItem::class, $item->id, null, ['surgery' => $surgery->id]);

        return $item;
    }

    /** Pull a batch back: unused packs are withdrawn; the operations that already used one are listed. @return list<array{label:string,surgery_id:int,patient_id:?int}> */
    public function recall(FacSterilisationBatch $batch, User $by, string $reason): array
    {
        if (in_array($batch->status, ['recalled', 'failed'], true) || mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why the batch is recalled.')]);
        }
        DB::transaction(function () use ($batch, $reason) {
            FacBatchItem::query()->where('batch_id', $batch->id)->where('status', 'packed')->update(['status' => 'withdrawn']);
            $batch->update(['status' => 'recalled', 'recall_reason' => mb_substr($reason, 0, 500)]);
        });
        $this->audit->log('UPDATE', FacSterilisationBatch::class, $batch->id, null, ['recalled' => true]);
        $used = $this->trace($batch);
        $used !== [] && $this->alerts->raise($batch->organization_id, 'critical', __('Batch :n was recalled and :u pack(s) were already used.', ['n' => $batch->number, 'u' => count($used)]), FacSterilisationBatch::class, $batch->id, 'fac-recall-'.$batch->id, 'facility.cssd.manage', true, $batch->branch_id);

        return $used;
    }

    /** Which operations used packs from this batch. @return list<array{label:string,surgery_id:int,patient_id:?int}> */
    public function trace(FacSterilisationBatch $batch): array
    {
        return FacBatchItem::query()->where('batch_id', $batch->id)->whereNotNull('surgery_id')->get()->map(fn ($i) => ['label' => $i->label, 'surgery_id' => (int) $i->surgery_id, 'patient_id' => $i->patient_id ? (int) $i->patient_id : null])->all();
    }

    /** Which batch did each pack used in an operation come from. @return list<FacBatchItem> */
    public function packsUsedIn(int $surgeryId): array
    {
        return FacBatchItem::query()->with('batch', 'set')->where('surgery_id', $surgeryId)->get()->all();
    }

    /** Packs past their date are withdrawn. @return int */
    public function withdrawExpired(int $organizationId): int
    {
        return FacBatchItem::query()->where('status', 'packed')->where('use_by', '<', today()->toDateString())->whereHas('batch', fn ($q) => $q->where('organization_id', $organizationId))->update(['status' => 'withdrawn']);
    }

    /** @param list<string> $states */
    private function mustBe(FacSterilisationBatch $batch, array $states): void
    {
        if (! in_array($batch->status, $states, true)) {
            throw ValidationException::withMessages(['status' => __('This batch is :s.', ['s' => $batch->status])]);
        }
    }
}
