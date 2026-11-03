<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Pharmacy dispensings with the time from the prescription being issued to the medicines being handed over. */
class DispensingFact extends Loader
{
    public function key(): string
    {
        return 'fact_dispensings';
    }

    public function label(): string
    {
        return 'Pharmacy dispensing';
    }

    protected function target(): string
    {
        return 'fact_dispensings';
    }

    protected function uniqueBy(): array
    {
        return ['dispensing_id'];
    }

    protected function source(): Builder
    {
        return DB::table('pharmacy_dispensings')->leftJoin('prescriptions', 'prescriptions.id', '=', 'pharmacy_dispensings.prescription_id')
            ->select('pharmacy_dispensings.id', 'pharmacy_dispensings.organization_id', 'pharmacy_dispensings.branch_id', 'pharmacy_dispensings.patient_id', 'pharmacy_dispensings.store_id', 'pharmacy_dispensings.status', 'pharmacy_dispensings.dispensed_at', 'pharmacy_dispensings.created_at', 'pharmacy_dispensings.updated_at',
                'prescriptions.issued_at', 'prescriptions.prescribed_at');
    }

    protected function watermarkColumn(): string
    {
        return 'pharmacy_dispensings.updated_at';
    }

    protected function idColumn(): string
    {
        return 'pharmacy_dispensings.id';
    }

    protected function map(Collection $rows): array
    {
        $items = DB::table('pharmacy_dispensing_items')->whereIn('dispensing_id', $rows->pluck('id')->all())->groupBy('dispensing_id')->selectRaw('dispensing_id, count(*) as n, sum(quantity_dispensed) as qty')->get()->keyBy('dispensing_id');
        $when = fn ($r) => $r->dispensed_at ?? $r->created_at;
        $keys = $this->patientKeys($rows, fn ($r) => $this->localDay($when($r)));

        return $rows->values()->map(fn ($r, $i) => ['dispensing_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->localDay($when($r)) ?? '19700101'), 'store_id' => $r->store_id,
            'patient_key' => $keys[$i], 'status' => $r->status, 'items' => (int) ($items[$r->id]->n ?? 0), 'quantity' => (string) ($items[$r->id]->qty ?? 0), 'tat_minutes' => $this->minutes($r->issued_at ?? $r->prescribed_at, $r->dispensed_at)])->all();
    }
}
