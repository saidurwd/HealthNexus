<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceFact extends Loader
{
    public function key(): string
    {
        return 'fact_invoices';
    }

    public function label(): string
    {
        return 'Invoices';
    }

    protected function target(): string
    {
        return 'fact_invoices';
    }

    protected function uniqueBy(): array
    {
        return ['invoice_id'];
    }

    protected function source(): Builder
    {
        return DB::table('billing_invoices')->where('billing_invoices.status', '<>', 'draft')->select('billing_invoices.id', 'billing_invoices.organization_id', 'billing_invoices.branch_id', 'billing_invoices.patient_id', 'billing_invoices.corporate_id', 'billing_invoices.insurance_policy_id', 'billing_invoices.invoice_type', 'billing_invoices.status', 'billing_invoices.subtotal', 'billing_invoices.discount_amount', 'billing_invoices.tax_amount', 'billing_invoices.rounding_amount', 'billing_invoices.grand_total', 'billing_invoices.paid_amount', 'billing_invoices.due_amount', 'billing_invoices.invoice_date', 'billing_invoices.finalized_at', 'billing_invoices.updated_at');
    }

    protected function watermarkColumn(): string
    {
        return 'billing_invoices.updated_at';
    }

    protected function idColumn(): string
    {
        return 'billing_invoices.id';
    }

    protected function map(Collection $rows): array
    {
        $day = fn ($r) => $this->localDay($r->finalized_at) ?? $this->day($r->invoice_date);
        $keys = $this->patientKeys($rows, $day);

        return $rows->values()->map(fn ($r, $i) => ['invoice_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $day($r)), 'patient_key' => $keys[$i],
            'invoice_type' => $r->invoice_type, 'status' => $r->status, 'payer_kind' => $r->corporate_id ? 'corporate' : ($r->insurance_policy_id ? 'insurance' : 'self'), 'is_billed' => $r->finalized_at !== null && $r->status !== 'cancelled',
            'subtotal' => $r->subtotal, 'discount' => $r->discount_amount, 'tax' => $r->tax_amount, 'rounding' => $r->rounding_amount, 'grand_total' => $r->grand_total, 'paid' => $r->paid_amount, 'due' => $r->due_amount])->all();
    }
}
