<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Asset;
use App\Models\Finance\Depreciation;
use App\Models\Finance\Journal;
use App\Models\Finance\Period;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Fixed assets: the register and straight-line monthly depreciation. (Where an asset physically is belongs to Phase 22.) */
class AssetService
{
    public function __construct(private readonly ChartService $chart, private readonly JournalService $journals, private readonly FinancePolicy $policy) {}

    /** @param array{tag:string,name:string,category?:?string,cost_center_id?:?int,acquired_on:string,cost:string,salvage_value?:string,life_months?:?int,depreciation_from?:?string,supplier_invoice_id?:?int,note?:?string} $d */
    public function register(int $organizationId, array $d, ?User $by = null): Asset
    {
        $cost = (string) $d['cost'];
        $salvage = (string) ($d['salvage_value'] ?? '0');
        $life = (int) ($d['life_months'] ?? $this->policy->int('asset.default_life_months', 60));
        if (! is_numeric($cost) || bccomp($cost, '0', 2) <= 0) {
            throw ValidationException::withMessages(['cost' => 'The cost must be above zero.']);
        }
        if (! is_numeric($salvage) || bccomp($salvage, $cost, 2) >= 0 || bccomp($salvage, '0', 2) < 0) {
            throw ValidationException::withMessages(['salvage_value' => 'The scrap value must be zero or more and less than the cost.']);
        }
        if ($life < 1 || $life > 600) {
            throw ValidationException::withMessages(['life_months' => 'The life is 1 to 600 months.']);
        }
        if (Asset::query()->where(['organization_id' => $organizationId, 'tag' => $d['tag']])->exists()) {
            throw ValidationException::withMessages(['tag' => 'That asset tag is already used.']);
        }
        $from = CarbonImmutable::parse($d['depreciation_from'] ?? $d['acquired_on'])->startOfMonth()->toDateString();

        return Asset::create(['organization_id' => $organizationId, 'tag' => $d['tag'], 'name' => $d['name'], 'category' => $d['category'] ?? null, 'cost_center_id' => $d['cost_center_id'] ?? null, 'acquired_on' => $d['acquired_on'], 'cost' => $cost, 'salvage_value' => $salvage,
            'life_months' => $life, 'depreciation_from' => $from, 'status' => 'active', 'supplier_invoice_id' => $d['supplier_invoice_id'] ?? null, 'note' => $d['note'] ?? null,
            'asset_account_id' => $this->chart->accountFor($organizationId, 'asset_cost')->id, 'accumulated_account_id' => $this->chart->accountFor($organizationId, 'accumulated_depreciation')->id, 'expense_account_id' => $this->chart->accountFor($organizationId, 'depreciation_expense')->id]);
    }

    /** Book the purchase: Dr the asset account, Cr the account the money came from or is owed to (bank, suppliers...). */
    public function postAcquisition(Asset $asset, string $creditAccountCode, User $by): Journal
    {
        $credit = Account::query()->where(['organization_id' => $asset->organization_id, 'code' => $creditAccountCode, 'postable' => true, 'active' => true])->first();
        if ($credit === null) {
            throw ValidationException::withMessages(['account' => 'Choose the account the asset was paid from or is owed to.']);
        }

        return $this->journals->postAuto($asset->organization_id, ['date' => $asset->acquired_on->toDateString(), 'memo' => 'Purchase of '.$asset->tag.' '.$asset->name, 'source_type' => 'asset_purchase', 'source_id' => (string) $asset->id, 'event_key' => 'acquired'], [
            ['account' => $asset->asset_account_id, 'debit' => (string) $asset->cost, 'credit' => '0', 'cost_center' => $asset->cost_center_id],
            ['account' => $credit->id, 'debit' => '0', 'credit' => (string) $asset->cost] + ($credit->control === 'payable' ? ['party_type' => 'supplier', 'party_id' => 0] : []),
        ], $by);
    }

    public function monthly(Asset $a): string
    {
        return bcdiv(bcsub((string) $a->cost, (string) $a->salvage_value, 2), (string) $a->life_months, 2);
    }

    /** The month's charge: the usual monthly amount, and in the last month whatever is left so the total is exact. */
    public function chargeFor(Asset $a, Period $period): string
    {
        if ($a->status === 'disposed' || $period->starts_on->lt($a->depreciation_from) || ($a->disposed_on && $period->starts_on->gt($a->disposed_on))) {
            return '0.00';
        }
        $depreciable = bcsub((string) $a->cost, (string) $a->salvage_value, 2);
        $left = bcsub($depreciable, (string) $a->accumulated, 2);
        if (bccomp($left, '0', 2) <= 0) {
            return '0.00';
        }
        $month = $this->monthly($a);

        return bccomp($month, $left, 2) > 0 || $this->isLastMonth($a, $period) ? $left : $month;
    }

    private function isLastMonth(Asset $a, Period $period): bool
    {
        $last = CarbonImmutable::parse($a->depreciation_from)->addMonths($a->life_months - 1)->startOfMonth();

        return $period->starts_on->gte($last);
    }

    /**
     * Charge every asset for one period in a single journal (one expense/accumulated pair per asset's accounts and cost centre). Running it again for the same period does nothing.
     *
     * @return array{assets:int,amount:string,journal:?string}
     */
    public function depreciate(int $organizationId, Period $period, ?User $by = null): array
    {
        if ($period->status !== 'open' && ! $this->policy->bool('period.post_into_locked', false)) {
            throw ValidationException::withMessages(['period' => 'The period is '.$period->status.'; depreciation cannot be posted into it.']);
        }

        return DB::transaction(function () use ($organizationId, $period, $by) {
            $total = '0.00';
            $lines = [];
            $charged = [];
            foreach (Asset::query()->where('organization_id', $organizationId)->whereIn('status', ['active'])->orderBy('id')->lockForUpdate()->get() as $a) {
                if (Depreciation::query()->where(['asset_id' => $a->id, 'period_id' => $period->id])->exists()) {
                    continue;
                }
                $amount = $this->chargeFor($a, $period);
                if (bccomp($amount, '0', 2) <= 0) {
                    continue;
                }
                $lines[] = ['account' => $a->expense_account_id, 'debit' => $amount, 'credit' => '0', 'cost_center' => $a->cost_center_id, 'memo' => $a->tag];
                $lines[] = ['account' => $a->accumulated_account_id, 'debit' => '0', 'credit' => $amount, 'memo' => $a->tag];
                $charged[] = [$a, $amount];
                $total = bcadd($total, $amount, 2);
            }
            if ($charged === []) {
                return ['assets' => 0, 'amount' => '0.00', 'journal' => null];
            }
            $journal = $this->journals->postAuto($organizationId, ['date' => $period->ends_on->toDateString(), 'memo' => 'Depreciation '.$period->name, 'kind' => 'depreciation', 'source_type' => 'depreciation', 'source_id' => (string) $period->id, 'event_key' => 'monthly'], $lines, $by, $this->policy->bool('period.post_into_locked', false));
            foreach ($charged as [$a, $amount]) {
                Depreciation::create(['asset_id' => $a->id, 'period_id' => $period->id, 'amount' => $amount, 'journal_id' => $journal->id, 'created_at' => now()]);
                $accumulated = bcadd((string) $a->accumulated, $amount, 2);
                $a->update(['accumulated' => $accumulated, 'status' => bccomp($accumulated, bcsub((string) $a->cost, (string) $a->salvage_value, 2), 2) >= 0 ? 'fully_depreciated' : 'active']);
            }

            return ['assets' => count($charged), 'amount' => $total, 'journal' => $journal->number];
        });
    }

    /** Book value = cost - accumulated depreciation. */
    public function bookValue(Asset $a): string
    {
        return bcsub((string) $a->cost, (string) $a->accumulated, 2);
    }

    /**
     * Retire an asset: remove cost and accumulated depreciation, and put the difference (loss, or gain if sold above book value) to the stock-adjustment/other account chosen by the accountant.
     */
    public function dispose(Asset $asset, string $date, string $proceeds, string $proceedsAccountCode, User $by): Asset
    {
        if ($asset->status === 'disposed') {
            throw ValidationException::withMessages(['status' => 'This asset was already disposed.']);
        }
        $org = $asset->organization_id;
        $proceedsAccount = Account::query()->where(['organization_id' => $org, 'code' => $proceedsAccountCode, 'postable' => true, 'active' => true])->first();
        if ($proceedsAccount === null || ! is_numeric($proceeds) || bccomp($proceeds, '0', 2) < 0) {
            throw ValidationException::withMessages(['proceeds' => 'Choose the account the money went to and give the amount received (0 if scrapped).']);
        }

        return DB::transaction(function () use ($asset, $date, $proceeds, $proceedsAccount, $by, $org) {
            $asset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $book = $this->bookValue($asset);
            $lines = [['account' => $asset->accumulated_account_id, 'debit' => (string) $asset->accumulated, 'credit' => '0'], ['account' => $asset->asset_account_id, 'debit' => '0', 'credit' => (string) $asset->cost]];
            bccomp($proceeds, '0', 2) > 0 && $lines[] = ['account' => $proceedsAccount->id, 'debit' => $proceeds, 'credit' => '0'];
            $diff = bcsub($book, $proceeds, 2); // positive = loss
            $result = $this->chart->accountFor($org, 'stock_adjustment')->id;
            if (bccomp($diff, '0', 2) > 0) {
                $lines[] = ['account' => $result, 'debit' => $diff, 'credit' => '0', 'memo' => 'Loss on disposal'];
            } elseif (bccomp($diff, '0', 2) < 0) {
                $lines[] = ['account' => $result, 'debit' => '0', 'credit' => ltrim($diff, '-'), 'memo' => 'Gain on disposal'];
            }
            $lines = array_values(array_filter($lines, fn ($l) => bccomp($l['debit'], '0', 2) > 0 || bccomp($l['credit'], '0', 2) > 0));
            $this->journals->postAuto($org, ['date' => $date, 'memo' => 'Disposal of '.$asset->tag.' '.$asset->name, 'source_type' => 'asset_disposal', 'source_id' => (string) $asset->id, 'event_key' => 'disposed'], $lines, $by);
            $asset->update(['status' => 'disposed', 'disposed_on' => $date]);

            return $asset->refresh();
        });
    }
}
