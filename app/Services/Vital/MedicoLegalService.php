<?php

namespace App\Services\Vital;

use App\Models\Patient;
use App\Models\User;
use App\Models\Vital\VrMedicolegalCase;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Medico-legal (police) cases: an injury or death the law wants the police told about. The case is registered, the police are informed (station and General Diary number), a post-mortem may be asked for, and the case is closed
 * once the police have what they need. A case on a body held in the mortuary holds its release until it is closed. The description and injuries are for those with the medico-legal right only.
 */
class MedicoLegalService
{
    public function __construct(private readonly NumberSeriesService $numbers, private readonly VitalPolicy $policy, private readonly AuditLogger $audit) {}

    /** @param  array<string,mixed>  $d */
    public function register(Patient $patient, array $d, User $by): VrMedicolegalCase
    {
        if (! in_array($d['kind'] ?? '', $this->policy->list('medicolegal_kinds'), true)) {
            throw ValidationException::withMessages(['kind' => __('Choose what kind of case this is.')]);
        }
        if (mb_strlen(trim((string) ($d['description'] ?? ''))) < 10) {
            throw ValidationException::withMessages(['description' => __('Describe what happened, in a few words.')]);
        }
        $brought = ! empty($d['brought_at']) ? CarbonImmutable::parse((string) $d['brought_at']) : CarbonImmutable::now();
        if ($brought->isFuture()) {
            throw ValidationException::withMessages(['brought_at' => __('The time cannot be in the future.')]);
        }

        return DB::transaction(function () use ($patient, $d, $by, $brought) {
            $mortuary = DB::table('fac_mortuary_cases')->where('organization_id', $patient->organization_id)->where('patient_id', $patient->id)->value('id');
            $case = VrMedicolegalCase::create([
                'organization_id' => $patient->organization_id, 'branch_id' => $d['branch_id'] ?? null, 'patient_id' => $patient->id, 'kind' => $d['kind'], 'brought_at' => $brought,
                'occurred_at' => ! empty($d['occurred_at']) ? $d['occurred_at'] : null, 'brought_by' => $d['brought_by'] ?? null, 'description' => trim((string) $d['description']), 'injuries' => $d['injuries'] ?? null,
                'status' => 'open', 'mortuary_case_id' => $mortuary, 'registered_by' => $by->id,
                'number' => $this->numbers->next('vital.medicolegal', (int) $patient->organization_id, prefix: 'ML', format: '{PREFIX}-{YEAR}-{SEQ:5}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id),
            ]);
            // The body is held in the mortuary until this case is closed.
            $mortuary && DB::table('fac_mortuary_cases')->where('id', $mortuary)->update(['police_case' => true, 'police_cleared' => false, 'updated_at' => now()]);
            $this->audit->log('CREATE', VrMedicolegalCase::class, $case->id, null, ['number' => $case->number, 'kind' => $case->kind]);

            return $case;
        });
    }

    /** @param  array<string,mixed>  $d  police_station, gd_number, case_number, officer_name, officer_contact, informed_at */
    public function informPolice(VrMedicolegalCase $case, array $d, User $by): VrMedicolegalCase
    {
        if ($case->status !== 'open') {
            throw ValidationException::withMessages(['status' => __('The police have already been told, or the case is closed.')]);
        }
        if (trim((string) ($d['police_station'] ?? '')) === '' || trim((string) ($d['gd_number'] ?? '')) === '') {
            throw ValidationException::withMessages(['gd_number' => __('Give the police station and the General Diary number.')]);
        }
        $at = ! empty($d['informed_at']) ? CarbonImmutable::parse((string) $d['informed_at']) : CarbonImmutable::now();
        if ($at->isFuture()) {
            throw ValidationException::withMessages(['informed_at' => __('The time cannot be in the future.')]);
        }
        $case->update(['status' => 'police_informed', 'police_station' => mb_substr(trim($d['police_station']), 0, 120), 'gd_number' => mb_substr(trim($d['gd_number']), 0, 40), 'case_number' => $d['case_number'] ?? null,
            'officer_name' => $d['officer_name'] ?? null, 'officer_contact' => $d['officer_contact'] ?? null, 'police_informed_at' => $at, 'police_informed_by' => $by->id]);
        $this->audit->log('UPDATE', VrMedicolegalCase::class, $case->id, ['status' => 'open'], ['status' => 'police_informed', 'gd_number' => $case->gd_number]);

        return $case;
    }

    public function requestPostmortem(VrMedicolegalCase $case, User $by): VrMedicolegalCase
    {
        if ($case->status !== 'police_informed') {
            throw ValidationException::withMessages(['status' => __('Tell the police first.')]);
        }
        $case->update(['status' => 'postmortem_requested', 'postmortem_requested_at' => now()]);
        $this->audit->log('UPDATE', VrMedicolegalCase::class, $case->id, ['status' => 'police_informed'], ['status' => 'postmortem_requested']);

        return $case;
    }

    public function recordPostmortem(VrMedicolegalCase $case, User $by): VrMedicolegalCase
    {
        if ($case->status !== 'postmortem_requested' || $case->postmortem_done_at !== null) {
            throw ValidationException::withMessages(['status' => __('No post-mortem is waiting to be recorded.')]);
        }
        $case->update(['postmortem_done_at' => now()]);
        $this->audit->log('UPDATE', VrMedicolegalCase::class, $case->id, null, ['postmortem_done_at' => now()->toIso8601String()]);

        return $case;
    }

    public function close(VrMedicolegalCase $case, string $note, User $by): VrMedicolegalCase
    {
        if ($case->status === 'open' || $case->status === 'closed') {
            throw ValidationException::withMessages(['status' => $case->status === 'open' ? __('Tell the police before closing the case.') : __('The case is already closed.')]);
        }
        if ($case->status === 'postmortem_requested' && $case->postmortem_done_at === null) {
            throw ValidationException::withMessages(['status' => __('The post-mortem that was asked for has not been recorded yet.')]);
        }
        if (mb_strlen(trim($note)) < 5) {
            throw ValidationException::withMessages(['closure_note' => __('Say, in a few words, why the case can be closed.')]);
        }
        DB::transaction(function () use ($case, $note, $by) {
            $before = $case->status;
            $case->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $by->id, 'closure_note' => mb_substr(trim($note), 0, 255)]);
            // The police have what they need: the body may now be released from the mortuary.
            $case->mortuary_case_id && DB::table('fac_mortuary_cases')->where('id', $case->mortuary_case_id)->update(['police_cleared' => true, 'updated_at' => now()]);
            $this->audit->log('UPDATE', VrMedicolegalCase::class, $case->id, ['status' => $before], ['status' => 'closed']);
        });

        return $case;
    }

    /** Cases the police have not been told about within the hours the hospital allows, oldest first. @return Collection<int, VrMedicolegalCase> */
    public function notInformedInTime(int $organizationId): Collection
    {
        return VrMedicolegalCase::query()->where('organization_id', $organizationId)->where('status', 'open')->where('brought_at', '<=', now()->subHours($this->policy->int('police_inform_hours', 24)))->orderBy('brought_at')->get();
    }
}
