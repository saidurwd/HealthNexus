<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueLineFact extends Loader
{
    public function key(): string
    {
        return 'fact_revenue_lines';
    }

    public function label(): string
    {
        return 'Revenue by service line';
    }

    protected function target(): string
    {
        return 'fact_revenue_lines';
    }

    protected function uniqueBy(): array
    {
        return ['invoice_item_id'];
    }

    protected function source(): Builder
    {
        return DB::table('billing_invoice_items')->join('billing_invoices', 'billing_invoices.id', '=', 'billing_invoice_items.invoice_id')->leftJoin('billing_charges', 'billing_charges.id', '=', 'billing_invoice_items.charge_id')
            ->leftJoin('billing_items', 'billing_items.id', '=', 'billing_invoice_items.billing_item_id')->leftJoin('billing_categories', 'billing_categories.id', '=', 'billing_items.category_id')->where('billing_invoices.status', '<>', 'draft')
            ->select('billing_invoice_items.id', 'billing_invoice_items.invoice_id', 'billing_invoices.organization_id', 'billing_invoices.branch_id', 'billing_invoices.status', 'billing_invoices.invoice_date', 'billing_invoices.finalized_at', 'billing_invoices.updated_at',
                'billing_invoice_items.gross_amount', 'billing_invoice_items.discount_amount', 'billing_invoice_items.tax_amount', 'billing_invoice_items.net_amount', 'billing_charges.department_id', 'billing_charges.provider_id', 'billing_categories.code as category_code');
    }

    protected function watermarkColumn(): string
    {
        return 'billing_invoices.updated_at';
    }

    protected function idColumn(): string
    {
        return 'billing_invoice_items.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['invoice_item_id' => $r->id, 'invoice_id' => $r->invoice_id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id,
            'date_key' => (int) str_replace('-', '', $this->localDay($r->finalized_at) ?? $this->day($r->invoice_date)), 'department_id' => $r->department_id, 'provider_id' => $r->provider_id, 'category_code' => $r->category_code,
            'is_billed' => $r->finalized_at !== null && $r->status !== 'cancelled', 'gross' => $r->gross_amount, 'discount' => $r->discount_amount, 'tax' => $r->tax_amount, 'net' => $r->net_amount])->all();
    }
}
