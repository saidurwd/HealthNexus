<?php

namespace App\Services\Welfare;

use App\Models\User;
use App\Models\Welfare\WfDonor;
use App\Models\Welfare\WfFund;
use App\Models\Welfare\WfFundEntry;
use App\Models\Welfare\WfGrant;
use App\Services\AuditLogger;
use App\Services\Numbering\NumberSeriesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Funds and the money in them. A fund's balance is the sum of its entries; entries are never changed (a database trigger refuses it), so a mistake is put right with a reversal that says why.
 * "Committed" is what approved grants may still take; "available" is balance minus committed, and a grant can only be approved against what is available.
 */
class FundService
{
    public function __construct(private readonly WelfarePolicy $policy, private readonly NumberSeriesService $numbers, private readonly AuditLogger $audit) {}

    /** @param array{name?:string,kind?:string,description?:?string,per_grant_cap?:mixed,low_balance_alert?:mixed} $d */
    public function createFund(int $organizationId, array $d, ?User $by = null): WfFund
    {
        $name = trim((string) ($d['name'] ?? ''));
        if (mb_strlen($name) < 3) {
            throw ValidationException::withMessages(['name' => __('Give the fund a name.')]);
        }
        if (! in_array($d['kind'] ?? '', $this->policy->list('fund_kinds'), true)) {
            throw ValidationException::withMessages(['kind' => __('Choose the kind of fund.')]);
        }
        if (WfFund::query()->where('organization_id', $organizationId)->where('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => __('A fund with that name already exists.')]);
        }
        $cap = isset($d['per_grant_cap']) && $d['per_grant_cap'] !== '' ? $this->money($d['per_grant_cap'], 'per_grant_cap') : null;
        $fund = WfFund::create(['organization_id' => $organizationId, 'name' => $name, 'kind' => $d['kind'], 'description' => $d['description'] ?? null, 'per_grant_cap' => $cap,
            'low_balance_alert' => isset($d['low_balance_alert']) && $d['low_balance_alert'] !== '' ? $this->money($d['low_balance_alert'], 'low_balance_alert') : 0, 'status' => 'active', 'created_by' => $by?->id]);
        $this->audit->log('CREATE', WfFund::class, $fund->id, null, ['name' => $name, 'kind' => $fund->kind]);

        return $fund;
    }

    public function close(WfFund $fund, User $by, string $reason): WfFund
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why the fund is closed.')]);
        }
        if (WfGrant::query()->where('fund_id', $fund->id)->whereIn('status', ['requested', 'approved'])->exists()) {
            throw ValidationException::withMessages(['fund' => __('A fund with open grants cannot be closed.')]);
        }
        $fund->update(['status' => 'closed']);
        $this->audit->log('UPDATE', WfFund::class, $fund->id, null, ['status' => 'closed', 'reason' => $reason]);

        return $fund;
    }

    /** @param array{name?:string,kind?:string,phone?:?string,anonymous?:mixed,note?:?string} $d */
    public function donor(int $organizationId, array $d): WfDonor
    {
        $name = trim((string) ($d['name'] ?? ''));
        if (mb_strlen($name) < 2) {
            throw ValidationException::withMessages(['donor_name' => __('Give the donor\'s name.')]);
        }

        return WfDonor::create(['organization_id' => $organizationId, 'name' => $name, 'kind' => in_array($d['kind'] ?? '', $this->policy->list('donor_kinds'), true) ? $d['kind'] : 'individual', 'phone' => $d['phone'] ?? null,
            'anonymous' => filter_var($d['anonymous'] ?? false, FILTER_VALIDATE_BOOLEAN), 'note' => $d['note'] ?? null]);
    }

    public function recordDonation(WfFund $fund, mixed $amount, ?WfDonor $donor, User $by, ?string $reference = null, ?string $note = null, ?\DateTimeInterface $at = null): WfFundEntry
    {
        $amount = $this->money($amount, 'amount');
        if ($fund->status !== 'active') {
            throw ValidationException::withMessages(['fund' => __('A closed fund takes no money.')]);
        }
        if ($donor && $donor->organization_id !== $fund->organization_id) {
            throw ValidationException::withMessages(['donor_id' => __('That donor does not belong here.')]);
        }

        return DB::transaction(function () use ($fund, $amount, $donor, $by, $reference, $note, $at) {
            $receipt = $this->numbers->next('wf.receipt', (int) $fund->organization_id, prefix: 'WR', format: '{PREFIX}-{YEAR}-{SEQ:6}', period: NumberSeriesService::PERIOD_YEAR, userId: $by->id);
            $entry = WfFundEntry::create(['organization_id' => $fund->organization_id, 'fund_id' => $fund->id, 'entry_type' => 'donation', 'amount' => $amount, 'donor_id' => $donor?->id, 'receipt_no' => $receipt, 'reference' => $reference,
                'note' => $note, 'occurred_at' => $at ?? now(), 'created_by' => $by->id]);
            $this->audit->log('CREATE', WfFundEntry::class, $entry->id, null, ['fund' => $fund->id, 'amount' => $amount, 'receipt' => $receipt]);

            return $entry;
        });
    }

    /** Put right a mistake: a reversal (negative) with the reason. It cannot take the fund below what approved grants may still use. */
    public function reverse(WfFundEntry $entry, User $by, string $reason): WfFundEntry
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => __('Say why the entry is reversed.')]);
        }

        return DB::transaction(function () use ($entry, $by, $reason) {
            $fund = WfFund::query()->lockForUpdate()->findOrFail($entry->fund_id);
            if ($entry->entry_type !== 'donation') {
                throw ValidationException::withMessages(['entry' => __('Only a donation can be reversed.')]);
            }
            if (WfFundEntry::query()->where('fund_id', $fund->id)->where('entry_type', 'reversal')->where('reference', 'reverses:'.$entry->public_id)->exists()) {
                throw ValidationException::withMessages(['entry' => __('That entry was already reversed.')]);
            }
            if (bccomp($this->available($fund), (string) $entry->amount, 2) < 0) {
                throw ValidationException::withMessages(['entry' => __('The fund no longer has this much free of approved grants.')]);
            }
            $rev = WfFundEntry::create(['organization_id' => $fund->organization_id, 'fund_id' => $fund->id, 'entry_type' => 'reversal', 'amount' => bcmul((string) $entry->amount, '-1', 2), 'donor_id' => $entry->donor_id, 'reference' => 'reverses:'.$entry->public_id,
                'note' => mb_substr($reason, 0, 300), 'occurred_at' => now(), 'created_by' => $by->id]);
            $this->audit->log('CREATE', WfFundEntry::class, $rev->id, null, ['reverses' => $entry->id, 'reason' => $reason]);

            return $rev;
        });
    }

    public function balance(WfFund $fund): string
    {
        return number_format((float) WfFundEntry::query()->where('fund_id', $fund->id)->sum('amount'), 2, '.', '');
    }

    /** What approved, still valid grants may yet take from the fund. */
    public function committed(WfFund $fund): string
    {
        $open = 0.0;
        $grants = WfGrant::query()->where('fund_id', $fund->id)->where('status', 'approved')->get(['cap_amount', 'spent_amount']);
        foreach ($grants as $g) {
            $open += max(0, (float) $g->cap_amount - (float) $g->spent_amount);
        }

        return number_format($open, 2, '.', '');
    }

    public function available(WfFund $fund): string
    {
        return bcsub($this->balance($fund), $this->committed($fund), 2);
    }

    private function money(mixed $v, string $field): string
    {
        if (! is_numeric($v) || (float) $v <= 0 || (float) $v > 99999999) {
            throw ValidationException::withMessages([$field => __('Enter an amount greater than zero.')]);
        }

        return number_format((float) $v, 2, '.', '');
    }
}
