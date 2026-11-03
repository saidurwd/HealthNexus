<?php

namespace App\Services\Clinical\Observations;

use App\Events\Clinical\ObservationRecorded;
use App\Models\Clinical\ClinicalObservation;
use App\Models\Clinical\ClinicalObservationSet;
use App\Models\Patient;
use App\Models\Terminology\CodeConcept;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only way to write a vital sign or measurement (guideline 10.4, decision D-08).
 * Observations are append-only: a wrong value is superseded (amend) or flagged entered_in_error, never edited or deleted.
 */
class ObservationService
{
    public function record(ObservationData $data, ?User $actor = null): ClinicalObservation
    {
        return DB::transaction(function () use ($data, $actor) {
            $this->assertPatientInOrganization($data);

            if ($data->sourceRecordId !== null) {
                // Idempotent for callers that mirror a record from another module.
                $existing = ClinicalObservation::query()
                    ->where(['source_module' => $data->sourceModule, 'source_record_id' => $data->sourceRecordId, 'code' => $this->resolve($data)['code']])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            return $this->insert($data, $actor, null);
        });
    }

    /**
     * A panel (blood pressure = systolic + diastolic) recorded as one set.
     *
     * @param  array<string, float>  $values  catalogue key => value, e.g. ['systolic_bp' => 120, 'diastolic_bp' => 80]
     * @return array{set: ClinicalObservationSet, observations: list<ClinicalObservation>}
     */
    public function recordPanel(string $panelKey, array $values, ObservationData $context, ?User $actor = null): array
    {
        $panel = config("observations.panels.$panelKey") ?? throw ValidationException::withMessages(['panel' => "Unknown panel [$panelKey]."]);

        $missing = array_diff($panel['members'], array_keys($values));
        if ($missing !== []) {
            throw ValidationException::withMessages(['panel' => 'Missing panel values: '.implode(', ', $missing)]);
        }

        return DB::transaction(function () use ($panel, $values, $context, $actor) {
            $this->assertPatientInOrganization($context);
            $effectiveAt = $context->effectiveAt ?? now();

            $set = ClinicalObservationSet::create([
                'organization_id' => $context->organizationId, 'hospital_id' => $context->hospitalId, 'branch_id' => $context->branchId,
                'patient_id' => $context->patientId, 'encounter_id' => $context->encounterId,
                'context_type' => $context->contextType, 'context_id' => $context->contextId,
                'panel_code' => $panel['code'], 'effective_at' => $effectiveAt, 'recorded_by' => $actor?->id,
            ]);

            $observations = [];
            foreach ($panel['members'] as $member) {
                $observations[] = $this->insert($this->with($context, key: $member, valueNumber: (float) $values[$member], effectiveAt: $effectiveAt), $actor, $set->id);
            }

            return ['set' => $set, 'observations' => $observations];
        });
    }

    /** Replaces a value: the new row (status `amended`) points at the old one, which stays untouched but is no longer current. */
    public function amend(ClinicalObservation $observation, float|string $newValue, string $reason, ?User $actor = null): ClinicalObservation
    {
        return DB::transaction(function () use ($observation, $newValue, $actor) {
            $locked = ClinicalObservation::query()->lockForUpdate()->findOrFail($observation->id);

            if (! in_array($locked->status, [ClinicalObservation::STATUS_FINAL, ClinicalObservation::STATUS_AMENDED], true) || $this->isSuperseded($locked)) {
                throw ValidationException::withMessages(['observation' => 'Only the current final value can be amended.']);
            }

            $key = $this->keyForCode($locked->code);
            $data = new ObservationData(
                organizationId: $locked->organization_id, patientId: $locked->patient_id, contextType: $locked->context_type,
                key: $key, valueNumber: is_numeric($newValue) ? (float) $newValue : null,
                valueText: is_numeric($newValue) ? null : (string) $newValue, unitCode: $locked->unit_code,
                effectiveAt: $locked->effective_at, branchId: $locked->branch_id, hospitalId: $locked->hospital_id,
                encounterId: $locked->encounter_id, contextId: $locked->context_id,
                code: $locked->code, display: $locked->display, codeSystem: $locked->code_system, sourceModule: 'observation',
            );

            $new = $this->insert($data, $actor, $locked->observation_set_id, supersedes: $locked->id, status: ClinicalObservation::STATUS_AMENDED);

            return $new;
        });
    }

    public function markEnteredInError(ClinicalObservation $observation, string $reason, ?User $actor = null): ClinicalObservation
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        $observation->forceFill(['status' => ClinicalObservation::STATUS_ENTERED_IN_ERROR, 'error_reason' => $reason])->save();

        return $observation;
    }

    private function insert(ObservationData $data, ?User $actor, ?int $setId, ?int $supersedes = null, ?string $status = null): ClinicalObservation
    {
        ['code' => $code, 'display' => $display, 'unit' => $unit] = $this->resolve($data);
        $this->validateValue($data, $code);

        $effectiveAt = $data->effectiveAt ?? now();
        $observation = ClinicalObservation::create([
            'organization_id' => $data->organizationId, 'hospital_id' => $data->hospitalId, 'branch_id' => $data->branchId,
            'patient_id' => $data->patientId, 'encounter_id' => $data->encounterId,
            'context_type' => $data->contextType, 'context_id' => $data->contextId, 'observation_set_id' => $setId,
            'code_system' => $data->codeSystem, 'code' => $code, 'display' => $display,
            'value_number' => $data->valueNumber, 'value_text' => $data->valueText, 'value_boolean' => $data->valueBoolean,
            'unit_code' => $unit, 'effective_at' => $effectiveAt, 'recorded_at' => now(), 'recorded_by' => $actor?->id,
            'device_id' => $data->deviceId, 'status' => $status ?? $data->status,
            'reference_low' => $data->referenceLow, 'reference_high' => $data->referenceHigh,
            'supersedes_id' => $supersedes, 'source_module' => $data->sourceModule, 'source_record_id' => $data->sourceRecordId,
        ]);

        ObservationRecorded::dispatch($observation->id, $observation->patient_id, $code, $data->contextType, $data->contextId, $data->organizationId);

        return $observation;
    }

    /** @return array{code: string, display: string, unit: ?string} */
    private function resolve(ObservationData $data): array
    {
        if ($data->key !== null) {
            $entry = config("observations.vitals.{$data->key}") ?? throw ValidationException::withMessages(['key' => "Unknown observation [{$data->key}]."]);

            if ($data->unitCode !== null && $data->unitCode !== $entry['unit']) {
                throw ValidationException::withMessages(['unit_code' => "{$entry['display']} must be recorded in {$entry['unit']}."]);
            }

            return ['code' => $entry['code'], 'display' => $entry['display'], 'unit' => $entry['unit']];
        }

        if ($data->code === null || $data->display === null) {
            throw ValidationException::withMessages(['code' => 'Give either a catalogue key or a code and display.']);
        }

        return ['code' => $data->code, 'display' => $data->display, 'unit' => $data->unitCode];
    }

    private function validateValue(ObservationData $data, string $code): void
    {
        if (! in_array($data->contextType, config('observations.contexts'), true)) {
            throw ValidationException::withMessages(['context_type' => "Unknown context [{$data->contextType}]."]);
        }

        if ($data->valueNumber === null && $data->valueText === null && $data->valueBoolean === null) {
            throw ValidationException::withMessages(['value' => 'A value is required.']);
        }

        $entry = collect(config('observations.vitals'))->firstWhere('code', $code);

        if ($entry && $data->valueNumber !== null && ($data->valueNumber < $entry['min'] || $data->valueNumber > $entry['max'])) {
            throw ValidationException::withMessages(['value' => "{$entry['display']} of {$data->valueNumber} {$entry['unit']} is outside the possible range {$entry['min']}–{$entry['max']}."]);
        }

        $unit = $entry['unit'] ?? $data->unitCode;
        if ($unit !== null && CodeConcept::whereHas('system', fn ($q) => $q->where('key', 'ucum'))->exists()
            && ! CodeConcept::whereHas('system', fn ($q) => $q->where('key', 'ucum'))->where('code', $unit)->exists()) {
            throw ValidationException::withMessages(['unit_code' => "Unit [$unit] is not a known UCUM code."]);
        }
    }

    private function assertPatientInOrganization(ObservationData $data): void
    {
        // The Patient global scope may be absent in jobs; check explicitly so a forged patient id cannot cross organizations.
        if (! Patient::withoutGlobalScopes()->whereKey($data->patientId)->where('organization_id', $data->organizationId)->exists()) {
            throw ValidationException::withMessages(['patient_id' => 'Patient not found.']);
        }
    }

    private function with(ObservationData $d, string $key, float $valueNumber, CarbonInterface $effectiveAt): ObservationData
    {
        return new ObservationData(
            organizationId: $d->organizationId, patientId: $d->patientId, contextType: $d->contextType, key: $key,
            valueNumber: $valueNumber, effectiveAt: $effectiveAt, branchId: $d->branchId, hospitalId: $d->hospitalId,
            encounterId: $d->encounterId, contextId: $d->contextId, deviceId: $d->deviceId,
            sourceModule: $d->sourceModule, sourceRecordId: $d->sourceRecordId ? $d->sourceRecordId.':'.$key : null,
        );
    }

    private function keyForCode(string $code): ?string
    {
        foreach (config('observations.vitals') as $key => $entry) {
            if ($entry['code'] === $code) {
                return $key;
            }
        }

        return null;
    }

    private function isSuperseded(ClinicalObservation $o): bool
    {
        return ClinicalObservation::where('supersedes_id', $o->id)->exists();
    }
}
