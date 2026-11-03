<?php

namespace App\Http\Controllers\Lookup;

use App\Models\Encounter;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Finds visits (encounters) for a searchable dropdown: the chosen patient's own visits, newest first, or by visit number or patient when no patient is chosen. Twenty at a time. */
class EncounterLookupController extends LookupController
{
    public function __invoke(Request $request): JsonResponse
    {
        $org = $this->organizationId($request);
        $query = Encounter::query()->where('encounters.organization_id', $org)->whereNull('encounters.deleted_at')->join('patients', 'patients.id', '=', 'encounters.patient_id')->where('patients.organization_id', $org);
        $patientId = (int) $request->query('patient_id', 0);
        $patientId > 0 && $query->where('encounters.patient_id', $patientId);
        foreach ($this->words($request) as $word) {
            $this->matching($query, ['encounters.encounter_no', 'encounters.encounter_type', 'patients.first_name', 'patients.last_name', 'patients.enterprise_patient_no'], $word);
        }
        $tz = (string) config('ux.timezone', 'Asia/Dhaka');

        return response()->json($query->orderByDesc(DB::raw('coalesce(encounters.started_at, encounters.created_at)'))->limit(20)
            ->get(['encounters.id', 'encounters.encounter_no', 'encounters.encounter_type', 'encounters.status', 'encounters.started_at', 'encounters.created_at', 'patients.first_name', 'patients.last_name'])
            ->map(fn ($e) => ['value' => (string) $e->id, 'text' => $e->encounter_no.' · '.$e->encounter_type.' · '.CarbonImmutable::parse($e->started_at ?? $e->created_at, 'UTC')->setTimezone($tz)->format('d M Y').($patientId > 0 ? '' : ' · '.trim($e->getAttribute('first_name').' '.$e->getAttribute('last_name')))])->values());
    }
}
