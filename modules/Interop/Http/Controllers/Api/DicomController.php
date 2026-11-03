<?php

namespace Modules\Interop\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interop\Dicom\DicomJson;
use App\Interop\Fhir\FhirActor;
use App\Models\Patient;
use App\Models\Radiology\RadiologyStudy;
use App\Services\Interop\DicomWorklistService;
use App\Services\Interop\ExchangeGuard;
use App\Services\Interop\InteropException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** DICOMweb-style answers for scanners and viewers: the modality worklist and study lookups. Same tokens, same hospital rules and same consent check as FHIR. */
class DicomController extends Controller
{
    public function __construct(private readonly DicomWorklistService $worklist, private readonly ExchangeGuard $guard) {}

    private function actor(Request $request): FhirActor
    {
        return $request->attributes->get('fhir_actor');
    }

    private function dicom(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status, ['Content-Type' => 'application/dicom+json']);
    }

    private function refuse(InteropException $e): JsonResponse
    {
        return response()->json(['message' => $e->getMessage(), 'code' => $e->code_], $e->status);
    }

    public function worklist(Request $request): JsonResponse
    {
        $a = $this->actor($request);
        $q = $this->worklist->query($a->organizationId, $request->only(['modality', 'ae', 'date', 'accession', 'status']) + ['patient_id' => $request->query('PatientID')]);
        $entries = $q->limit(200)->get();
        foreach ($entries as $e) {
            $this->guard->log($a->partner, null, 'ModalityWorklist', $e->accession_number, 'read', 'allowed', null, $a->user);
        }

        return $this->dicom($entries->map(fn ($e) => DicomJson::worklistItem($e))->values()->all());
    }

    public function status(Request $request, string $accession): JsonResponse
    {
        $a = $this->actor($request);
        if (! $a->canWrite) {
            return $this->refuse(new InteropException('This token cannot report progress (it needs the scope dicom.write).', 403, 'forbidden'));
        }
        $d = $request->validate(['status' => ['required', 'string']]);
        try {
            $entry = $this->worklist->reportStatus($a->organizationId, $accession, $d['status'], $a->partner);
        } catch (InteropException $e) {
            return $this->refuse($e);
        }

        return $this->dicom(DicomJson::worklistItem($entry));
    }

    public function studies(Request $request): JsonResponse
    {
        $a = $this->actor($request);
        $q = RadiologyStudy::query()->where('organization_id', $a->organizationId)->when($request->query('AccessionNumber'), fn ($w, $v) => $w->where('accession_number', $v))
            ->when($request->query('StudyInstanceUID'), fn ($w, $v) => $w->where('study_instance_uid', $v))->when($request->query('ModalitiesInStudy'), fn ($w, $v) => $w->where('modality', $v));
        if ($mrn = $request->query('PatientID')) {
            $q->whereIn('patient_id', Patient::query()->where(['organization_id' => $a->organizationId, 'enterprise_patient_no' => $mrn])->select('id'));
        } elseif (! $request->query('AccessionNumber') && ! $request->query('StudyInstanceUID')) {
            return $this->refuse(new InteropException('Give PatientID, AccessionNumber or StudyInstanceUID.', 400, 'required'));
        }
        $out = [];
        foreach ($q->orderByDesc('study_date')->limit(100)->get() as $s) {
            $patient = Patient::query()->find($s->patient_id);
            if ($patient === null || ($a->partner !== null && ! $this->guard->check($a->partner, $patient, 'ImagingStudy', 'search', $s->accession_number))) {
                continue; // no consent: the study is simply not in the answer
            }
            $out[] = DicomJson::study($s, str_replace(' ', '^', trim($patient->last_name.' '.$patient->first_name)), $patient->enterprise_patient_no);
        }

        return $this->dicom($out);
    }
}
