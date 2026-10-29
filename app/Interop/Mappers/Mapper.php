<?php

namespace App\Interop\Mappers;

use App\Services\Interop\IdMapper;
use App\Services\Interop\TerminologyService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * One FHIR resource type over our records (guideline override 4: an adapter, not a second database). A mapper names the tables it reads (`sources()`), how to find a patient's rows in each,
 * the search parameters it understands, and turns one row into FHIR JSON. Ids shown outside are public ids; numeric ids never appear.
 */
abstract class Mapper
{
    public function __construct(protected readonly IdMapper $ids, protected readonly TerminologyService $terminology) {}

    abstract public function type(): string;

    /**
     * @return list<array{model:class-string<Model>,entity:string,patient?:string|callable,search?:array<string,callable>}> `patient` is the column holding the patient id, or a callable ($query, $patientId) for rows that
     *                                                                                                                      reach the patient through another table; `search` maps a FHIR search parameter to a callable ($query, $value)
     */
    abstract public function sources(): array;

    /** @return array<string, mixed> */
    abstract public function toFhir(Model $m): array;

    /** The patient a row is about (null for things that are not about one patient, like a doctor or the hospital); consent and scope checks start here. */
    public function patientIdOf(Model $m): ?int
    {
        $pid = $m->getAttribute('patient_id');

        return $pid === null ? null : (int) $pid;
    }

    protected function id(Model $m, ?string $entity = null): string
    {
        return $this->ids->expose($m, $entity);
    }

    /** @param class-string<Model> $class */
    protected function ref(string $type, string $class, int|string|null $localId, ?string $display = null, ?string $entity = null): ?array
    {
        if ($localId === null || $localId === '') {
            return null;
        }
        $model = $class::query()->find($localId);
        if ($model === null) {
            return null;
        }

        return array_filter(['reference' => $type.'/'.$this->id($model, $entity), 'display' => $display], fn ($v) => $v !== null);
    }

    /** Columns without a date cast arrive as strings; both are accepted. */
    protected function time(CarbonInterface|string|null $t): ?string
    {
        return $t === null || $t === '' ? null : Carbon::parse($t)->utc()->format('Y-m-d\TH:i:s\Z');
    }

    protected function date(CarbonInterface|string|null $d): ?string
    {
        return $d === null || $d === '' ? null : Carbon::parse($d)->format('Y-m-d');
    }

    /** @return array<string, mixed> */
    protected function base(Model $m, ?string $entity = null): array
    {
        return ['resourceType' => $this->type(), 'id' => $this->id($m, $entity), 'meta' => array_filter(['lastUpdated' => $this->time($m->getAttribute('updated_at'))])];
    }

    protected function coding(string $system, string $code, ?string $display = null): array
    {
        return array_filter(['system' => $system, 'code' => $code, 'display' => $display], fn ($v) => $v !== null && $v !== '');
    }
}
