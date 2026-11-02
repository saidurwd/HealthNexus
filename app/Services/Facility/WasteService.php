<?php

namespace App\Services\Facility;

use App\Models\Facility\FacWasteManifest;
use App\Models\Facility\FacWasteRecord;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Biomedical waste: weighed where it is made, collected in a manifest for the contractor, handed over, and confirmed with the disposal certificate. A manifest's weight is exactly the weight of the records in it. */
class WasteService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly FacilityPolicy $policy, private readonly AuditLogger $audit) {}

    public function record(int $organizationId, string $category, string $location, float $kg, User $by, ?string $date = null, ?int $branchId = null): FacWasteRecord
    {
        if (! in_array($category, $this->policy->list('waste.categories'), true)) {
            throw ValidationException::withMessages(['category' => __('Choose the kind of waste.')]);
        }
        if (mb_strlen(trim($location)) < 2 || $kg <= 0 || $kg > 5000) {
            throw ValidationException::withMessages(['weight_kg' => __('Say where it was made and how much it weighs.')]);
        }
        $date ??= today()->toDateString();
        if (strtotime($date) > strtotime(today()->toDateString())) {
            throw ValidationException::withMessages(['recorded_on' => __('The date cannot be in the future.')]);
        }

        return FacWasteRecord::create(['organization_id' => $organizationId, 'branch_id' => $branchId, 'recorded_on' => $date, 'category' => $category, 'location' => mb_substr(trim($location), 0, 80), 'weight_kg' => round($kg, 2), 'recorded_by' => $by->id]);
    }

    /** Gather every record not yet on a manifest, up to the collection date. */
    public function manifest(int $organizationId, string $contractor, ?string $vehicle, ?string $collectedOn = null, ?int $branchId = null): FacWasteManifest
    {
        $date = $collectedOn ?: today()->toDateString();
        if (mb_strlen(trim($contractor)) < 3) {
            throw ValidationException::withMessages(['contractor' => __('Name the contractor.')]);
        }
        $open = FacWasteRecord::query()->where('organization_id', $organizationId)->whereNull('manifest_id')->where('recorded_on', '<=', $date);
        if (! $open->exists()) {
            throw ValidationException::withMessages(['collected_on' => __('There is no waste waiting to be collected.')]);
        }

        return DB::transaction(function () use ($organizationId, $contractor, $vehicle, $date, $branchId) {
            $m = FacWasteManifest::create(['organization_id' => $organizationId, 'branch_id' => $branchId, 'number' => $this->numbers->next('fac.manifest', $organizationId, null, prefix: 'WM', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR),
                'contractor' => mb_substr(trim($contractor), 0, 150), 'vehicle_no' => $vehicle ? mb_substr($vehicle, 0, 30) : null, 'collected_on' => $date, 'status' => 'prepared']);
            FacWasteRecord::query()->where('organization_id', $organizationId)->whereNull('manifest_id')->where('recorded_on', '<=', $date)->update(['manifest_id' => $m->id]);
            $m->update(['total_kg' => (float) FacWasteRecord::query()->where('manifest_id', $m->id)->sum('weight_kg')]);
            $this->audit->log('CREATE', FacWasteManifest::class, $m->id, null, ['number' => $m->number, 'kg' => $m->total_kg]);

            return $m->refresh();
        });
    }

    public function handOver(FacWasteManifest $m, User $by): FacWasteManifest
    {
        if ($m->status !== 'prepared') {
            throw ValidationException::withMessages(['status' => __('This manifest has been handed over.')]);
        }
        $this->mustAdd($m);
        $m->update(['status' => 'handed_over', 'handed_over_by' => $by->id]);
        $this->audit->log('UPDATE', FacWasteManifest::class, $m->id, null, ['status' => 'handed_over']);

        return $m;
    }

    public function confirm(FacWasteManifest $m, string $certificate, ?string $disposedOn = null): FacWasteManifest
    {
        if ($m->status !== 'handed_over') {
            throw ValidationException::withMessages(['status' => __('Hand the waste over first.')]);
        }
        if (mb_strlen(trim($certificate)) < 3) {
            throw ValidationException::withMessages(['certificate_no' => __('Enter the disposal certificate number.')]);
        }
        $m->update(['status' => 'confirmed', 'certificate_no' => mb_substr(trim($certificate), 0, 60), 'disposed_on' => $disposedOn ?: today()->toDateString()]);
        $this->audit->log('UPDATE', FacWasteManifest::class, $m->id, null, ['status' => 'confirmed']);

        return $m;
    }

    /** @return array<string,float> kilograms by category in a period */
    public function totals(int $organizationId, string $from, string $to): array
    {
        return FacWasteRecord::query()->where('organization_id', $organizationId)->whereBetween('recorded_on', [$from, $to])->selectRaw('category, sum(weight_kg) as kg')->groupBy('category')->pluck('kg', 'category')->map(fn ($v) => round((float) $v, 2))->all();
    }

    private function mustAdd(FacWasteManifest $m): void
    {
        $sum = (float) FacWasteRecord::query()->where('manifest_id', $m->id)->sum('weight_kg');
        if (abs(round($sum, 2) - round((float) $m->total_kg, 2)) > (float) $this->policy->get('waste.tolerance_kg', 0.0) + 0.0001) {
            throw ValidationException::withMessages(['total_kg' => __('The manifest total does not match the records in it.')]);
        }
    }
}
