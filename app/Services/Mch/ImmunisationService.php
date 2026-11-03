<?php

namespace App\Services\Mch;

use App\Models\Mch\MchImmunisation;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A child's routine immunisation (EPI). The schedule is the hospital's setting (config/mch.php is an EXAMPLE the programme must confirm): each dose has an age in days after birth. A child's card shows what was given, what is due now
 * and what is overdue. A dose is recorded once; a dose given elsewhere can be entered from the child's card ("recorded") so it is not given twice.
 */
class ImmunisationService
{
    public function __construct(private readonly MchPolicy $policy, private readonly AuditLogger $audit) {}

    /** @return array<string, array{0:string,1:int,2:string}> */
    public function schedule(): array
    {
        return (array) $this->policy->get('epi', []);
    }

    /**
     * Every dose of the schedule for a child, with its state on the day: given, due (the age has come), overdue (past the grace), or upcoming.
     *
     * @return list<array{vaccine:string,name:string,group:string,due_on:string,state:string,given_at:?string}>
     */
    public function card(Patient $child, ?CarbonImmutable $on = null): array
    {
        $on ??= CarbonImmutable::now();
        $dob = $this->dob($child);
        $given = MchImmunisation::query()->where('patient_id', $child->id)->get()->keyBy('vaccine');
        $grace = $this->policy->int('overdue_grace_days', 28);
        $out = [];
        foreach ($this->schedule() as $key => [$name, $days, $group]) {
            $due = $dob->addDays((int) $days);
            $g = $given[$key] ?? null;
            $state = $g ? 'given' : ($on->startOfDay()->gt($due->addDays($key === 'bcg' || $key === 'opv0' ? $this->policy->int('birth_dose_days', 14) : $grace)) ? 'overdue' : ($on->startOfDay()->gte($due) ? 'due' : 'upcoming'));
            $out[] = ['vaccine' => $key, 'name' => $name, 'group' => $group, 'due_on' => $due->toDateString(), 'state' => $state, 'given_at' => $g?->given_at?->toDateTimeString()];
        }

        return $out;
    }

    /** @param  array<string,mixed>  $d  given_at, batch_no, site, note, status */
    public function give(Patient $child, string $vaccine, array $d, User $by): MchImmunisation
    {
        if (! isset($this->schedule()[$vaccine])) {
            throw ValidationException::withMessages(['vaccine' => __('Choose a dose from the schedule.')]);
        }
        if (MchImmunisation::query()->where(['patient_id' => $child->id, 'vaccine' => $vaccine])->exists()) {
            throw ValidationException::withMessages(['vaccine' => __('This dose is already recorded for this child.')]);
        }
        $at = ! empty($d['given_at']) ? CarbonImmutable::parse((string) $d['given_at']) : CarbonImmutable::now();
        if ($at->isFuture() || $at->startOfDay()->lt($this->dob($child))) {
            throw ValidationException::withMessages(['given_at' => __('The date cannot be in the future or before the child was born.')]);
        }
        $recorded = ($d['status'] ?? 'given') === 'recorded';
        if (! $recorded && trim((string) ($d['batch_no'] ?? '')) === '') {
            throw ValidationException::withMessages(['batch_no' => __('Give the batch number of the vaccine used.')]);
        }

        return DB::transaction(function () use ($child, $vaccine, $d, $by, $at, $recorded) {
            $i = MchImmunisation::create([
                'organization_id' => $child->organization_id, 'branch_id' => $d['branch_id'] ?? null, 'patient_id' => $child->id, 'vaccine' => $vaccine, 'given_at' => $at, 'batch_no' => ($d['batch_no'] ?? '') !== '' ? mb_substr(trim($d['batch_no']), 0, 40) : null,
                'site' => $d['site'] ?? null, 'status' => $recorded ? 'recorded' : 'given', 'note' => isset($d['note']) ? mb_substr((string) $d['note'], 0, 255) : null, 'given_by' => $by->id,
            ]);
            $this->audit->log('CREATE', MchImmunisation::class, $i->id, null, ['patient_id' => $child->id, 'vaccine' => $vaccine, 'status' => $i->status]);

            return $i;
        });
    }

    /**
     * Children (born less than the oldest dose plus the grace ago) who have a dose due or overdue, with how many.
     *
     * @return Collection<int, object{patient_id:int,due:int,overdue:int}>
     */
    public function dueList(int $organizationId, ?CarbonImmutable $on = null): Collection
    {
        $on ??= CarbonImmutable::now();
        $horizon = max(array_map(fn ($v) => $v[1], $this->schedule())) + $this->policy->int('overdue_grace_days', 28);
        $children = Patient::query()->where('organization_id', $organizationId)->whereNull('deceased_at')->whereNotNull('date_of_birth')->where('date_of_birth', '>=', $on->subDays($horizon)->toDateString())->where('date_of_birth', '<=', $on->toDateString())->get();
        $out = collect();
        foreach ($children as $c) {
            $card = collect($this->card($c, $on));
            $due = $card->where('state', 'due')->count();
            $over = $card->where('state', 'overdue')->count();
            if ($due + $over > 0) {
                $out->push((object) ['patient_id' => $c->id, 'due' => $due, 'overdue' => $over]);
            }
        }

        return $out->sortByDesc('overdue')->values();
    }

    private function dob(Patient $child): CarbonImmutable
    {
        if ($child->date_of_birth === null) {
            throw ValidationException::withMessages(['patient_id' => __('The child\'s date of birth is not recorded.')]);
        }

        return CarbonImmutable::parse($child->date_of_birth)->startOfDay();
    }
}
