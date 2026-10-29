<?php

namespace App\Services\Interop;

use App\Interop\Fhir\FhirActor;
use App\Interop\Fhir\FhirValidator;
use App\Interop\Mappers\Mapper;
use App\Models\Clinical\ClinicalObservation;
use App\Models\Concerns\HasPublicId;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\EncounterProcedure;
use App\Models\Laboratory\LabOrder;
use App\Models\Laboratory\LabOrderItem;
use App\Models\Laboratory\LabReport;
use App\Models\Patient;
use App\Models\PatientConsent;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Radiology\RadiologyOrder;
use App\Models\Radiology\RadiologyOrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** The FHIR server as an adapter over our records (guideline override 4). Reading, searching, bundles for a patient or a visit, and the capability statement. Consent and tenancy are decided here, once. */
class FhirService
{
    /** @var array<string, Mapper> */
    private array $mappers = [];

    public function __construct(private readonly IdMapper $ids, private readonly ExchangeGuard $guard, private readonly InteropPolicy $policy, private readonly FhirValidator $validator, private readonly PatientIntakeService $intake) {}

    public function mapper(string $type): Mapper
    {
        if (! in_array($type, (array) $this->policy->get('fhir.resources', []), true)) {
            throw new InteropException("Resource type $type is not supported by this server.", 404, 'not-supported');
        }

        return $this->mappers[$type] ??= app('App\\Interop\\Mappers\\'.$type.'Mapper');
    }

    // ---- read -----------------------------------------------------------------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function read(FhirActor $a, string $type, string $id): array
    {
        [$mapper, $model, $source] = $this->locate($a, $type, $id);
        $this->authorizeRow($a, $mapper, $model, $type, $id, 'read');

        return $mapper->toFhir($model);
    }

    /** @return array{0:Mapper,1:Model,2:array<string,mixed>} */
    private function locate(FhirActor $a, string $type, string $id): array
    {
        $mapper = $this->mapper($type);
        foreach ($mapper->sources() as $source) {
            $class = $source['model'];
            $q = $class::query();
            if (in_array(HasPublicId::class, class_uses_recursive($class), true)) {
                $q->where('public_id', $id);
            } else {
                $local = $this->ids->resolve($source['entity'], $id, $a->organizationId);
                if ($local === null) {
                    continue;
                }
                $q->whereKey($local);
            }
            $model = $q->first();
            if ($model !== null && $this->orgOf($model, $source, $mapper) === $a->organizationId) {
                return [$mapper, $model, $source];
            }
        }
        throw new InteropException("$type/$id was not found.", 404, 'not-found');
    }

    private function orgOf(Model $m, array $source, Mapper $mapper): ?int
    {
        if (($source['scope'] ?? null) === 'id') {
            return (int) $m->getKey();
        }
        if ($m->getAttribute('organization_id') !== null) {
            return (int) $m->getAttribute('organization_id');
        }
        $pid = $mapper->patientIdOf($m); // order lines and the like have no hospital column: they belong to their patient's hospital

        return $pid === null ? null : Patient::query()->whereKey($pid)->value('organization_id');
    }

    private function authorizeRow(FhirActor $a, Mapper $mapper, Model $m, string $type, string $id, string $action): void
    {
        $pid = $mapper->patientIdOf($m);
        if ($pid === null) {
            return; // not about one patient (a doctor, the hospital)
        }
        $patient = Patient::query()->find($pid);
        if ($patient === null || $patient->organization_id !== $a->organizationId || ($type === 'Patient' && $patient->merged_into_patient_id !== null)) {
            throw new InteropException("$type/$id was not found.", 404, 'not-found');
        }
        if ($a->partner !== null && ! $this->guard->check($a->partner, $patient, $type, $action, $id)) {
            throw new InteropException('This patient has not agreed to share this record with you.', 403, 'forbidden');
        }
        if ($a->partner === null) {
            $this->guard->log(null, $patient, $type, $id, $action, 'allowed', null, $a->user);
        }
    }

    // ---- search ---------------------------------------------------------------------------------------------------------------------------------

    /** @return array<string, mixed> a searchset Bundle */
    public function search(FhirActor $a, string $type, array $params, string $selfUrl): array
    {
        $mapper = $this->mapper($type);
        $count = max(1, min((int) ($params['_count'] ?? $this->policy->int('fhir.page_size', 20)), $this->policy->int('fhir.max_page_size', 100)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $known = array_merge(['_count', '_id', '_sort', 'page', 'patient', 'subject', 'beneficiary', '_lastUpdated'], ...array_map(fn ($s) => array_keys($s['search'] ?? []), $mapper->sources()));
        $unknown = array_diff(array_keys($params), $known);
        if ($unknown !== []) {
            throw new InteropException('Search parameter not supported: '.implode(', ', $unknown), 400, 'not-supported');
        }

        $patientRef = $params['patient'] ?? $params['subject'] ?? $params['beneficiary'] ?? null;
        $patient = null;
        if ($patientRef !== null) {
            $patient = $this->resolvePatient($a, preg_replace('#^Patient/#', '', (string) $patientRef));
            if ($a->partner !== null && ! $this->guard->check($a->partner, $patient, $type, 'search', null)) {
                throw new InteropException('This patient has not agreed to share records with you.', 403, 'forbidden');
            }
            $a->partner === null && $this->guard->log(null, $patient, $type, null, 'search', 'allowed', null, $a->user);
        } elseif ($type !== 'Patient' && isset($mapper->sources()[0]['patient'])) {
            throw new InteropException("Searching $type needs a patient (patient=<id>).", 400, 'required');
        }

        $rows = collect();
        $total = 0;
        foreach ($mapper->sources() as $source) {
            $q = $this->query($a, $source, $params, $patient, $type);
            $total += (clone $q)->count();
            $rows = $rows->concat($q->orderByDesc($q->getModel()->getTable().'.updated_at')->orderByDesc($q->getModel()->getQualifiedKeyName())->limit($page * $count)->get());
        }
        $slice = $rows->sortByDesc(fn (Model $m) => ($m->getAttribute('updated_at')->timestamp ?? 0))->values()->slice(($page - 1) * $count, $count)->values();
        if ($a->partner !== null && $patient === null && $type === 'Patient') {
            $slice->each(fn ($m) => $this->guard->log($a->partner, $m, 'Patient', null, 'search', 'allowed'));
        }
        $base = rtrim($this->baseUrl(), '/');
        $next = $total > $page * $count ? $selfUrl.(str_contains($selfUrl, '?') ? '&' : '?').http_build_query(['page' => $page + 1, '_count' => $count]) : null;

        return ['resourceType' => 'Bundle', 'type' => 'searchset', 'total' => $total, 'link' => array_filter([['relation' => 'self', 'url' => $selfUrl], $next ? ['relation' => 'next', 'url' => $next] : null]),
            'entry' => $slice->map(fn (Model $m) => ['fullUrl' => $base.'/'.$type.'/'.$this->ids->expose($m, $this->entityOf($mapper, $m)), 'resource' => $mapper->toFhir($m), 'search' => ['mode' => 'match']])->all()];
    }

    private function entityOf(Mapper $mapper, Model $m): string
    {
        foreach ($mapper->sources() as $s) {
            if ($m instanceof $s['model']) {
                return $s['entity'];
            }
        }

        return class_basename($m);
    }

    private function query(FhirActor $a, array $source, array $params, ?Patient $patient, string $type): Builder
    {
        $class = $source['model'];
        $q = $class::query();
        $table = $q->getModel()->getTable();
        if (($source['scope'] ?? null) === 'id') {
            $q->where("$table.id", $a->organizationId);
        } elseif (Schema::hasColumn($table, 'organization_id')) {
            $q->where("$table.organization_id", $a->organizationId);
        }
        if ($type === 'Patient') {
            $q->whereNull('merged_into_patient_id');
            if ($a->partner !== null && $a->partner->requires_consent) {
                $consent = $a->partner->consent_type ?: 'data_sharing';
                $q->whereIn('patients.id', PatientConsent::query()->where(['consent_type' => $consent, 'status' => 'active'])->select('patient_id'));
            }
        }
        if ($patient !== null && isset($source['patient'])) {
            is_callable($source['patient']) ? $source['patient']($q, $patient->id) : $q->where($table.'.'.$source['patient'], $patient->id);
        }
        if (isset($params['_id'])) {
            in_array(HasPublicId::class, class_uses_recursive($class), true) ? $q->where("$table.public_id", $params['_id']) : $q->whereKey($this->ids->resolve($source['entity'], (string) $params['_id'], $a->organizationId) ?? 0);
        }
        foreach ($source['search'] ?? [] as $name => $apply) {
            if (isset($params[$name]) && $params[$name] !== '') {
                $apply($q, (string) $params[$name]);
            }
        }

        return $q;
    }

    private function resolvePatient(FhirActor $a, string $id): Patient
    {
        $p = Patient::query()->where(['organization_id' => $a->organizationId, 'public_id' => $id])->whereNull('merged_into_patient_id')->first();
        if ($p === null) {
            throw new InteropException("Patient/$id was not found.", 404, 'not-found');
        }

        return $p;
    }

    // ---- bundles --------------------------------------------------------------------------------------------------------------------------------

    /** @return array<string, mixed> everything we hold for a patient, as one Bundle (the "document" a partner asks for) */
    public function everything(FhirActor $a, string $patientId): array
    {
        $patient = $this->resolvePatient($a, $patientId);
        if ($a->partner !== null && ! $this->guard->check($a->partner, $patient, 'Patient', 'operation', $patientId)) {
            throw new InteropException('This patient has not agreed to share this record with you.', 403, 'forbidden');
        }
        $a->partner === null && $this->guard->log(null, $patient, 'Patient', $patientId, 'operation', 'allowed', null, $a->user);
        $entries = [$this->entry('Patient', $this->mapper('Patient')->toFhir($patient))];
        foreach (['Encounter', 'Appointment', 'Condition', 'Observation', 'AllergyIntolerance', 'Procedure', 'ServiceRequest', 'DiagnosticReport', 'ImagingStudy', 'MedicationRequest', 'Coverage'] as $type) {
            $mapper = $this->mapper($type);
            foreach ($mapper->sources() as $source) {
                $q = $this->query($a, $source, [], $patient, $type);
                foreach ($q->orderByDesc($q->getModel()->getQualifiedKeyName())->limit(200)->get() as $m) {
                    $entries[] = $this->entry($type, $mapper->toFhir($m));
                }
            }
        }

        return ['resourceType' => 'Bundle', 'type' => 'collection', 'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'), 'total' => count($entries), 'entry' => $entries];
    }

    /** @return array<string, mixed> a visit as a Bundle: the encounter, who it was for, what was found and done */
    public function encounterBundle(FhirActor $a, string $encounterId): array
    {
        [$mapper, $enc] = $this->locate($a, 'Encounter', $encounterId);
        /** @var Encounter $enc */
        $this->authorizeRow($a, $mapper, $enc, 'Encounter', $encounterId, 'operation');
        $patient = Patient::query()->findOrFail($enc->patient_id);
        $entries = [$this->entry('Encounter', $mapper->toFhir($enc)), $this->entry('Patient', $this->mapper('Patient')->toFhir($patient))];
        $add = function (string $type, $models) use (&$entries) {
            $m = $this->mapper($type);
            foreach ($models as $row) {
                $entries[] = $this->entry($type, $m->toFhir($row));
            }
        };
        $add('Condition', Diagnosis::query()->where('encounter_id', $enc->id)->get());
        $add('Observation', ClinicalObservation::query()->where('encounter_id', $enc->id)->get());
        $add('Procedure', EncounterProcedure::query()->where('encounter_id', $enc->id)->get());
        $add('MedicationRequest', PrescriptionItem::query()->whereIn('prescription_id', Prescription::query()->where('encounter_id', $enc->id)->whereNotNull('issued_at')->select('id'))->get());
        $labOrders = LabOrder::query()->where('encounter_id', $enc->id)->pluck('id');
        $add('ServiceRequest', LabOrderItem::query()->whereIn('lab_order_id', $labOrders)->get());
        $add('DiagnosticReport', LabReport::query()->whereIn('lab_order_id', $labOrders)->whereIn('status', ['final', 'amended'])->get());
        $radOrders = RadiologyOrder::query()->where('encounter_id', $enc->id)->pluck('id');
        $add('ServiceRequest', RadiologyOrderItem::query()->whereIn('radiology_order_id', $radOrders)->get());

        return ['resourceType' => 'Bundle', 'type' => 'collection', 'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'), 'total' => count($entries), 'entry' => $entries];
    }

    private function entry(string $type, array $resource): array
    {
        return ['fullUrl' => rtrim($this->baseUrl(), '/').'/'.$type.'/'.$resource['id'], 'resource' => $resource];
    }

    private function baseUrl(): string
    {
        return url((string) $this->policy->get('fhir.base_path', '/api/v1/fhir'));
    }

    /** @return array<string, mixed> */
    public function capability(): array
    {
        $resources = [];
        foreach ((array) $this->policy->get('fhir.resources', []) as $type) {
            $mapper = $this->mapper($type);
            $params = array_unique(array_merge(...array_map(fn ($s) => array_keys($s['search'] ?? []), $mapper->sources())));
            if ($mapper->sources()[0]['patient'] ?? false) {
                $params[] = 'patient';
            }
            $resources[] = array_filter(['type' => $type, 'interaction' => array_merge([['code' => 'read'], ['code' => 'search-type']], $type === 'Patient' ? [['code' => 'create'], ['code' => 'update']] : []),
                'searchParam' => array_map(fn ($p) => ['name' => $p, 'type' => 'string'], array_values($params)), 'operation' => $type === 'Patient' ? [['name' => 'everything', 'definition' => 'http://hl7.org/fhir/OperationDefinition/Patient-everything']] : ($type === 'Encounter' ? [['name' => 'everything', 'definition' => 'http://hl7.org/fhir/OperationDefinition/Encounter-everything']] : null)]);
        }

        return ['resourceType' => 'CapabilityStatement', 'status' => 'active', 'date' => now()->format('Y-m-d'), 'kind' => 'instance', 'software' => ['name' => 'HealthNexus HMS'], 'fhirVersion' => (string) $this->policy->get('fhir.version', '4.0.1'),
            'format' => ['json'], 'rest' => [['mode' => 'server', 'security' => ['service' => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/restful-security-service', 'code' => 'Bearer']]]], 'description' => 'Client tokens with scopes fhir.read / fhir.write. Patient data is shared only with a patient\'s consent.'], 'resource' => $resources,
                'operation' => [['name' => 'validate', 'definition' => 'http://hl7.org/fhir/OperationDefinition/Resource-validate'], ['name' => 'lookup', 'definition' => 'http://hl7.org/fhir/OperationDefinition/CodeSystem-lookup'], ['name' => 'translate', 'definition' => 'http://hl7.org/fhir/OperationDefinition/ConceptMap-translate']]]]];
    }

    // ---- write (Patient only) -------------------------------------------------------------------------------------------------------------------

    /** @return array{0:array<string,mixed>,1:bool} the resource and whether it was created (false = it already existed) */
    public function writePatient(FhirActor $a, array $resource, ?string $id = null): array
    {
        if (! $a->canWrite) {
            throw new InteropException('This token cannot write (it needs the scope fhir.write).', 403, 'forbidden');
        }
        $issues = $this->validator->validate($resource + ['resourceType' => 'Patient'], $a->partner->profile ?? 'r4');
        if ($issues !== [] || ($resource['resourceType'] ?? null) !== 'Patient') {
            throw new InteropException('The Patient does not pass validation: '.implode('; ', array_column($issues ?: [['diagnostics' => 'resourceType must be Patient']], 'diagnostics')), 422, 'invalid');
        }
        $intake = $this->intakeFrom($resource);
        if ($id !== null) {
            $existing = $this->resolvePatient($a, $id);
            $intake['mrn'] = $existing->enterprise_patient_no;
        }
        [$patient, $created] = $this->intake->upsert($a->organizationId, $a->partner, $intake);
        $this->guard->log($a->partner, $patient, 'Patient', $this->ids->expose($patient), $created ? 'create' : 'update', 'allowed', null, $a->user);

        return [$this->mapper('Patient')->toFhir($patient), $created];
    }

    /** @return array<string, mixed> */
    private function intakeFrom(array $r): array
    {
        $name = $r['name'][0] ?? [];
        $given = (array) ($name['given'] ?? []);
        $telecom = collect($r['telecom'] ?? []);
        $national = null;
        $external = null;
        foreach ($r['identifier'] ?? [] as $i) {
            $system = $i['system'] ?? '';
            if ($system === config('interop.fhir.patient_identifier_systems.national_id')) {
                $national = $i['value'] ?? null;
            } elseif ($system !== config('interop.fhir.patient_identifier_systems.mrn')) {
                $external ??= $i['value'] ?? null;
            }
        }
        $mrn = collect($r['identifier'] ?? [])->firstWhere('system', config('interop.fhir.patient_identifier_systems.mrn'))['value'] ?? null;

        return ['first_name' => $given[0] ?? null, 'middle_name' => $given[1] ?? null, 'last_name' => $name['family'] ?? null, 'date_of_birth' => isset($r['birthDate']) && strlen((string) $r['birthDate']) === 10 ? $r['birthDate'] : null,
            'sex' => match ($r['gender'] ?? null) {
                'male' => 'M', 'female' => 'F', 'other' => 'O', default => null
            }, 'phone' => $telecom->firstWhere('system', 'phone')['value'] ?? null, 'email' => $telecom->firstWhere('system', 'email')['value'] ?? null,
            'national_identifier' => $national, 'address' => $r['address'][0]['text'] ?? (isset($r['address'][0]['line']) ? implode(', ', (array) $r['address'][0]['line']) : null), 'city' => $r['address'][0]['city'] ?? null, 'external_id' => $external, 'mrn' => $mrn];
    }
}
