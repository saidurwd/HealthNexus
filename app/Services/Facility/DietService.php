<?php

namespace App\Services\Facility;

use App\Models\Facility\FacDietOrder;
use App\Models\Facility\FacDietType;
use App\Models\Facility\FacMealList;
use App\Models\Facility\FacMealTray;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Patient diets. A patient in a bed has at most one active diet order (allergies and texture on it); the meal list for a ward and meal is made from the active orders, trays are served, refused or held for fasting,
 * and the kitchen's requisition is the day's count by diet. Orders end when the patient is discharged.
 */
class DietService
{
    public function __construct(private readonly FacilityPolicy $policy, private readonly AuditLogger $audit) {}

    public function type(int $organizationId, string $code, string $name, ?string $description = null): FacDietType
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen(trim($name)) < 2) {
            throw ValidationException::withMessages(['name' => __('Give the diet a code and a name.')]);
        }

        return FacDietType::query()->updateOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => mb_substr(trim($name), 0, 100), 'description' => $description ? mb_substr($description, 0, 300) : null, 'active' => true]);
    }

    /** @param array<string,mixed> $d texture, allergies, note, starts_on, ward_id */
    public function order(int $organizationId, int $admissionId, FacDietType $diet, array $d, User $by): FacDietOrder
    {
        $admission = DB::table('ipd_admissions')->where('organization_id', $organizationId)->where('id', $admissionId)->first(['id', 'patient_id', 'branch_id', 'status', 'admitted_at', 'actual_discharge_date']);
        if (! $admission || ! $this->inHospital($admission)) {
            throw ValidationException::withMessages(['admission' => __('Choose a patient who is in hospital.')]);
        }
        if ($diet->organization_id !== $organizationId || ! $diet->active) {
            throw ValidationException::withMessages(['diet_type_id' => __('Choose a diet.')]);
        }
        $texture = $d['texture'] ?? 'normal';
        if (! in_array($texture, $this->policy->list('diet.textures'), true)) {
            throw ValidationException::withMessages(['texture' => __('Choose the texture.')]);
        }

        return DB::transaction(function () use ($organizationId, $admission, $diet, $d, $texture, $by) {
            FacDietOrder::query()->where(['admission_id' => $admission->id, 'status' => 'active'])->update(['status' => 'stopped', 'ends_on' => today()->subDay()->toDateString()]);
            $o = FacDietOrder::create(['organization_id' => $organizationId, 'branch_id' => $admission->branch_id, 'admission_id' => $admission->id, 'patient_id' => $admission->patient_id, 'ward_id' => $d['ward_id'] ?? null, 'diet_type_id' => $diet->id,
                'texture' => $texture, 'allergies' => isset($d['allergies']) && trim((string) $d['allergies']) !== '' ? mb_substr(trim($d['allergies']), 0, 300) : null, 'note' => isset($d['note']) ? mb_substr((string) $d['note'], 0, 300) : null,
                'starts_on' => $d['starts_on'] ?? today()->toDateString(), 'status' => 'active', 'ordered_by' => $by->id]);
            $this->audit->log('CREATE', FacDietOrder::class, $o->id, null, ['diet' => $diet->code, 'texture' => $texture]);

            return $o;
        });
    }

    public function stop(FacDietOrder $o, User $by): FacDietOrder
    {
        if ($o->status !== 'active') {
            throw ValidationException::withMessages(['status' => __('Already stopped.')]);
        }
        $o->update(['status' => 'stopped', 'ends_on' => today()->toDateString()]);
        $this->audit->log('UPDATE', FacDietOrder::class, $o->id, null, ['status' => 'stopped']);

        return $o;
    }

    /** The list for one meal and ward on a day: one tray for each active order. Safe to ask again. */
    public function mealList(int $organizationId, string $date, string $meal, ?int $wardId = null, ?int $branchId = null): FacMealList
    {
        if (! in_array($meal, $this->policy->list('diet.meals'), true)) {
            throw ValidationException::withMessages(['meal' => __('Choose the meal.')]);
        }
        $list = FacMealList::query()->firstOrCreate(['organization_id' => $organizationId, 'service_date' => $date, 'meal' => $meal, 'ward_id' => $wardId], ['branch_id' => $branchId, 'status' => 'prepared']);
        if ($list->status === 'served') {
            return $list;
        }
        $orders = FacDietOrder::query()->where('organization_id', $organizationId)->where('status', 'active')->where('starts_on', '<=', $date)->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $date))
            ->when($wardId, fn ($q) => $q->where('ward_id', $wardId))->get();
        foreach ($orders as $o) {
            FacMealTray::query()->firstOrCreate(['meal_list_id' => $list->id, 'diet_order_id' => $o->id], ['status' => $o->texture === 'nil_by_mouth' ? 'held' : 'planned']);
        }

        return $list->refresh();
    }

    public function serve(FacMealTray $tray, string $status, ?string $note = null): FacMealTray
    {
        if (! in_array($status, ['served', 'refused', 'held'], true)) {
            throw ValidationException::withMessages(['status' => __('Choose served, refused or held.')]);
        }
        $list = FacMealList::query()->find($tray->meal_list_id);
        if ($list && $list->status === 'served') {
            throw ValidationException::withMessages(['status' => __('This meal has been closed.')]);
        }
        if ($status === 'served' && $tray->order->texture === 'nil_by_mouth') {
            throw ValidationException::withMessages(['status' => __('This patient is fasting: do not serve a meal.')]);
        }
        $tray->update(['status' => $status, 'note' => $note ? mb_substr($note, 0, 200) : null]);

        return $tray;
    }

    /** Close the meal: every tray must have been dealt with. */
    public function closeList(FacMealList $list, User $by): FacMealList
    {
        if ($list->status === 'served') {
            throw ValidationException::withMessages(['status' => __('Already closed.')]);
        }
        $left = FacMealTray::query()->where('meal_list_id', $list->id)->where('status', 'planned')->count();
        if ($left > 0) {
            throw ValidationException::withMessages(['status' => __(':n tray(s) are not yet served, refused or held.', ['n' => $left])]);
        }
        $list->update(['status' => 'served', 'served_by' => $by->id, 'served_at' => now()]);

        return $list;
    }

    /** What the kitchen has to make on a day: trays by diet and meal. @return array<string,array<string,int>> */
    public function requisition(int $organizationId, string $date): array
    {
        $out = [];
        $orders = FacDietOrder::query()->with('diet')->where('organization_id', $organizationId)->where('status', 'active')->where('starts_on', '<=', $date)->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $date))->where('texture', '!=', 'nil_by_mouth')->get();
        foreach ($orders->groupBy(fn ($o) => $o->diet->name.' / '.$o->texture) as $label => $group) {
            foreach ($this->policy->list('diet.meals') as $meal) {
                $out[$label][$meal] = $group->count();
            }
        }

        return $out;
    }

    /** Orders for patients who have gone home end. @return int */
    public function endForDischarged(int $organizationId): int
    {
        $ids = FacDietOrder::query()->where('organization_id', $organizationId)->where('status', 'active')->pluck('admission_id', 'id');
        if ($ids->isEmpty()) {
            return 0;
        }
        $gone = DB::table('ipd_admissions')->whereIn('id', $ids->values())->where(fn ($q) => $q->whereNotNull('actual_discharge_date')->orWhereIn('status', ['discharged', 'closed']))->pluck('id');

        return FacDietOrder::query()->where('organization_id', $organizationId)->where('status', 'active')->whereIn('admission_id', $gone)->update(['status' => 'stopped', 'ends_on' => today()->toDateString()]);
    }

    /** Was the patient in hospital at this moment? (By the clock, so a stay in the past can be written up as it was.) */
    private function inHospital(object $admission): bool
    {
        return $admission->status !== 'cancelled' && CarbonImmutable::parse((string) $admission->admitted_at)->lte(now())
            && ($admission->actual_discharge_date === null || CarbonImmutable::parse((string) $admission->actual_discharge_date)->gt(now()));
    }
}
