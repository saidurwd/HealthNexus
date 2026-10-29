<?php

namespace App\Services\Finance;

use App\Models\Finance\FeeShareRule;
use App\Models\Finance\FeeShareRun;
use App\Models\Finance\Period;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Doctor-wise revenue and the consultants' share of it, from rules (never a spreadsheet): a percent of net revenue per doctor and, if wanted, per kind of service. */
class FeeShareService
{
    public function __construct(private readonly ChartService $chart, private readonly JournalService $journals, private readonly FinancePolicy $policy) {}

    public function saveRule(int $organizationId, ?int $providerUserId, ?string $categoryCode, string $percent, ?string $from = null, ?string $to = null, ?string $note = null): FeeShareRule
    {
        if (! is_numeric($percent) || bccomp($percent, '0', 2) <= 0 || bccomp($percent, '100', 2) > 0) {
            throw ValidationException::withMessages(['percent' => 'The share is a percent above 0 and up to 100.']);
        }

        return FeeShareRule::create(['organization_id' => $organizationId, 'provider_user_id' => $providerUserId, 'category_code' => $categoryCode ?: null, 'percent' => $percent, 'effective_from' => $from, 'effective_to' => $to, 'note' => $note, 'active' => true]);
    }

    /**
     * Net revenue of invoices finalised in the range, per doctor and Billing category (after the invoice's discounts; cancelled invoices excluded).
     *
     * @return Collection<int,\stdClass> provider_id, provider, category, revenue, items
     */
    public function doctorRevenue(int $organizationId, string $from, string $to): Collection
    {
        return DB::table('billing_invoice_items as ii')->join('billing_invoices as inv', 'inv.id', '=', 'ii.invoice_id')->leftJoin('billing_charges as ch', 'ch.id', '=', 'ii.charge_id')->leftJoin('users as u', 'u.id', '=', 'ch.provider_id')
            ->leftJoin('billing_items as bi', 'bi.id', '=', 'ii.billing_item_id')->leftJoin('billing_categories as bc', 'bc.id', '=', 'bi.category_id')
            ->where('inv.organization_id', $organizationId)->whereNotNull('inv.finalized_at')->whereNotIn('inv.status', ['draft', 'cancelled'])->whereBetween(DB::raw('date(inv.finalized_at)'), [$from, $to])
            ->groupBy('ch.provider_id', 'u.name', 'bc.code')->orderBy('u.name')->selectRaw('ch.provider_id as provider_id, u.name as provider, bc.code as category, coalesce(sum(ii.net_amount), 0) as revenue, count(ii.id) as items')->get()
            ->map(function ($r) {
                $r->revenue = bcadd((string) $r->revenue, '0', 2);

                return $r;
            });
    }

    /** The share for each doctor: the most specific active rule applies (doctor + category, then doctor, then category, then everyone). @return Collection<int,array{provider_id:int,provider:string,revenue:string,amount:string}> */
    public function compute(int $organizationId, string $from, string $to): Collection
    {
        $rules = FeeShareRule::query()->where(['organization_id' => $organizationId, 'active' => true])->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $to))->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from))->get();
        $out = [];
        foreach ($this->doctorRevenue($organizationId, $from, $to) as $r) {
            if (! $r->provider_id) {
                continue;
            }
            $rule = $rules->first(fn ($x) => $x->provider_user_id === (int) $r->provider_id && $x->category_code === $r->category)
                ?? $rules->first(fn ($x) => $x->provider_user_id === (int) $r->provider_id && $x->category_code === null)
                ?? $rules->first(fn ($x) => $x->provider_user_id === null && $x->category_code === $r->category)
                ?? $rules->first(fn ($x) => $x->provider_user_id === null && $x->category_code === null);
            if ($rule === null) {
                continue;
            }
            $share = bcdiv(bcmul($r->revenue, (string) $rule->percent, 4), '100', 2);
            $out[$r->provider_id] ??= ['provider_id' => (int) $r->provider_id, 'provider' => (string) $r->provider, 'revenue' => '0.00', 'amount' => '0.00'];
            $out[$r->provider_id]['revenue'] = bcadd($out[$r->provider_id]['revenue'], $r->revenue, 2);
            $out[$r->provider_id]['amount'] = bcadd($out[$r->provider_id]['amount'], $share, 2);
        }

        return collect($out)->values();
    }

    /** Accrue the month's shares: Dr professional fee share, Cr doctors' fees payable. One run per doctor and period; running again adds nothing. @return array{doctors:int,amount:string} */
    public function accrue(int $organizationId, Period $period, User $by): array
    {
        $shares = $this->compute($organizationId, $period->starts_on->toDateString(), $period->ends_on->toDateString())->filter(fn ($s) => bccomp($s['amount'], '0', 2) > 0);
        $n = 0;
        $total = '0.00';
        DB::transaction(function () use ($shares, $organizationId, $period, $by, &$n, &$total) {
            foreach ($shares as $s) {
                if (FeeShareRun::query()->where(['organization_id' => $organizationId, 'period_id' => $period->id, 'provider_user_id' => $s['provider_id']])->exists()) {
                    continue;
                }
                $journal = $this->journals->postAuto($organizationId, ['date' => $period->ends_on->toDateString(), 'memo' => 'Doctor fee share '.$s['provider'].' '.$period->name, 'source_type' => 'fee_share', 'source_id' => $period->id.':'.$s['provider_id'], 'event_key' => 'accrued'], [
                    ['account' => $this->chart->accountFor($organizationId, 'fee_share_expense')->id, 'debit' => $s['amount'], 'credit' => '0'],
                    ['account' => $this->chart->accountFor($organizationId, 'fee_share_payable')->id, 'debit' => '0', 'credit' => $s['amount'], 'party_type' => 'provider', 'party_id' => $s['provider_id']],
                ], $by, $this->policy->bool('period.post_into_locked', false));
                FeeShareRun::create(['organization_id' => $organizationId, 'period_id' => $period->id, 'provider_user_id' => $s['provider_id'], 'revenue_base' => $s['revenue'], 'amount' => $s['amount'], 'status' => 'accrued', 'journal_id' => $journal->id, 'created_by' => $by->id]);
                $n++;
                $total = bcadd($total, $s['amount'], 2);
            }
        });

        return ['doctors' => $n, 'amount' => $total];
    }
}
