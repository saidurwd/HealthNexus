<?php

namespace App\Services\Analytics\Loaders;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentFact extends Loader
{
    public function key(): string
    {
        return 'fact_payments';
    }

    public function label(): string
    {
        return 'Payments received';
    }

    protected function target(): string
    {
        return 'fact_payments';
    }

    protected function uniqueBy(): array
    {
        return ['payment_id'];
    }

    protected function source(): Builder
    {
        return DB::table('billing_payments')->leftJoin('billing_payment_methods', 'billing_payment_methods.id', '=', 'billing_payments.payment_method_id')->select('billing_payments.id', 'billing_payments.organization_id', 'billing_payments.branch_id', 'billing_payments.payment_date', 'billing_payments.status', 'billing_payments.amount', 'billing_payments.updated_at', 'billing_payment_methods.type as method_type');
    }

    protected function watermarkColumn(): string
    {
        return 'billing_payments.updated_at';
    }

    protected function idColumn(): string
    {
        return 'billing_payments.id';
    }

    protected function map(Collection $rows): array
    {
        return $rows->map(fn ($r) => ['payment_id' => $r->id, 'organization_id' => $r->organization_id, 'branch_id' => $r->branch_id, 'date_key' => (int) str_replace('-', '', $this->day($r->payment_date)), 'method_type' => $r->method_type, 'status' => $r->status, 'amount' => $r->amount])->all();
    }
}
