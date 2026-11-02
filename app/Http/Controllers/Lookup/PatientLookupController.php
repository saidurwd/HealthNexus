<?php

namespace App\Http\Controllers\Lookup;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Finds patients for a searchable dropdown (data-remote). A dropdown that held the first fifty patients could never find the fifty-first; this finds anyone in the signed-in person's hospital by name, patient number, phone or
 * national ID, twenty at a time.
 */
class PatientLookupController extends LookupController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $this->organizationId($request, $user->can('viewAny', Patient::class) || $user->can('create', Appointment::class) || $user->can('create', Encounter::class) ? ['patients.view', 'encounters.view'] : []);
        $q = trim((string) $request->query('q', ''));
        $query = Patient::query()->where('organization_id', $org)->whereNull('merged_into_patient_id');
        foreach ($this->words($request) as $word) {
            $this->matching($query, ['first_name', 'last_name', 'middle_name', 'enterprise_patient_no', 'phone', 'national_identifier'], $word);
        }

        return response()->json($query->orderByRaw('case when enterprise_patient_no = ? then 0 else 1 end', [$q])->orderBy('first_name')->orderBy('last_name')->limit(20)->get()
            ->map(fn (Patient $p) => ['value' => (string) $p->id, 'text' => $p->full_name.' ('.($p->enterprise_patient_no ?? 'N/A').')'])->values());
    }
}
