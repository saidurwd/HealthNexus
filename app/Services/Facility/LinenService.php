<?php

namespace App\Services\Facility;

use App\Models\Facility\FacLinenMovement;
use App\Models\Facility\FacLinenType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Linen as a ledger: every movement is a row that is never changed (a mistake is a correcting movement). Where the linen is now is worked out from the rows: the clean store, the wards, the soiled pile,
 * the laundry. A movement that would take more out of a place than is in it is refused.
 */
class LinenService
{
    public function __construct(private readonly FacilityPolicy $policy, private readonly FacilityAlerts $alerts) {}

    public function type(int $organizationId, string $code, string $name, ?int $par = null): FacLinenType
    {
        $code = strtoupper(trim($code));
        if ($code === '' || mb_strlen(trim($name)) < 2) {
            throw ValidationException::withMessages(['name' => __('Give the linen a code and a name.')]);
        }

        return FacLinenType::query()->updateOrCreate(['organization_id' => $organizationId, 'code' => $code], ['name' => mb_substr(trim($name), 0, 100), 'par_level' => $par ?? $this->policy->int('linen.default_par', 60), 'active' => true]);
    }

    public function move(FacLinenType $type, string $movement, int $quantity, User $by, ?string $location = null, ?string $date = null, ?string $note = null, ?int $branchId = null): FacLinenMovement
    {
        if (! in_array($movement, $this->policy->list('linen.movements'), true)) {
            throw ValidationException::withMessages(['movement' => __('Choose the kind of movement.')]);
        }
        if ($quantity < 1 || $quantity > 100000) {
            throw ValidationException::withMessages(['quantity' => __('Say how many.')]);
        }
        $date ??= today()->toDateString();
        if (strtotime($date) > strtotime(today()->toDateString())) {
            throw ValidationException::withMessages(['moved_on' => __('The date cannot be in the future.')]);
        }
        $from = ['issue' => 'clean_store', 'collect_soiled' => 'on_wards', 'send_laundry' => 'soiled', 'receive_clean' => 'at_laundry', 'condemn' => 'soiled'][$movement] ?? null;
        $b = $this->balance($type);
        if ($from && $b[$from] < $quantity) {
            throw ValidationException::withMessages(['quantity' => __('Only :n are in that place.', ['n' => $b[$from]])]);
        }
        $row = FacLinenMovement::create(['organization_id' => $type->organization_id, 'branch_id' => $branchId, 'linen_type_id' => $type->id, 'movement' => $movement, 'location' => $location ? mb_substr($location, 0, 80) : null, 'quantity' => $quantity,
            'moved_on' => $date, 'recorded_by' => $by->id, 'note' => $note ? mb_substr($note, 0, 200) : null]);
        $after = $this->balance($type);
        $after['clean_store'] < $type->par_level && $this->alerts->raise($type->organization_id, 'info', __('Clean stock of :t is below its level (:n of :p).', ['t' => $type->name, 'n' => $after['clean_store'], 'p' => $type->par_level]), FacLinenType::class, $type->id, 'fac-linen-par-'.$type->id.'-'.today()->toDateString(), 'facility.linen.manage');

        return $row;
    }

    /** @return array{clean_store:int,on_wards:int,soiled:int,at_laundry:int,condemned:int,total:int} */
    public function balance(FacLinenType $type): array
    {
        $sum = FacLinenMovement::query()->where('linen_type_id', $type->id)->selectRaw('movement, sum(quantity) as q')->groupBy('movement')->pluck('q', 'movement');
        $q = fn (string $m) => (int) ($sum[$m] ?? 0);
        $out = ['clean_store' => $q('receive_new') + $q('receive_clean') - $q('issue'), 'on_wards' => $q('issue') - $q('collect_soiled'), 'soiled' => $q('collect_soiled') - $q('send_laundry') - $q('condemn'),
            'at_laundry' => $q('send_laundry') - $q('receive_clean'), 'condemned' => $q('condemn')];
        $out['total'] = $out['clean_store'] + $out['on_wards'] + $out['soiled'] + $out['at_laundry'];

        return $out;
    }
}
