<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\TaxCode;
use App\Models\Finance\TaxEntry;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * VAT output and input, tax withheld (AIT/TDS and VAT at source): the register, the return worksheet and a check that the register agrees with the ledger.
 * The rates are never here: documents carry their own tax, tax codes are the accountant's setting. Nothing in this class is tax advice (OD-03).
 */
class TaxService
{
    public const KINDS = ['vat_output', 'vat_input', 'ait', 'tds', 'vds', 'other'];

    public function __construct(private readonly LedgerService $ledger, private readonly ChartService $chart) {}

    /** @param array{code:string,name:string,kind:string,rate:string,account_id:int,effective_from?:?string,effective_to?:?string,note?:?string} $d */
    public function saveCode(int $organizationId, array $d, ?TaxCode $existing = null): TaxCode
    {
        if (! in_array($d['kind'], self::KINDS, true)) {
            throw ValidationException::withMessages(['kind' => 'Choose a kind of tax.']);
        }
        if (! is_numeric($d['rate']) || bccomp((string) $d['rate'], '0', 4) < 0 || bccomp((string) $d['rate'], '100', 4) > 0) {
            throw ValidationException::withMessages(['rate' => 'The rate is a percent between 0 and 100. Confirm it with the hospital\'s accountant.']);
        }
        $account = Account::query()->where('organization_id', $organizationId)->where('postable', true)->find($d['account_id']);
        if ($account === null) {
            throw ValidationException::withMessages(['account_id' => 'Choose the account this tax is booked to.']);
        }
        $dupe = TaxCode::query()->where(['organization_id' => $organizationId, 'code' => $d['code']])->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists();
        if ($dupe) {
            throw ValidationException::withMessages(['code' => 'That tax code is already used.']);
        }
        $data = ['organization_id' => $organizationId, 'code' => $d['code'], 'name' => $d['name'], 'kind' => $d['kind'], 'rate' => $d['rate'], 'account_id' => $account->id, 'effective_from' => $d['effective_from'] ?? null, 'effective_to' => $d['effective_to'] ?? null, 'note' => $d['note'] ?? null];

        return $existing ? tap($existing)->update($data) : TaxCode::create($data);
    }

    /** @return Collection<int,TaxEntry> */
    public function register(int $organizationId, string $kind, string $from, string $to): Collection
    {
        return TaxEntry::query()->where(['organization_id' => $organizationId, 'kind' => $kind])->whereBetween('entry_date', [$from, $to])->orderBy('entry_date')->orderBy('id')->get();
    }

    /**
     * The worksheet for a VAT return period: output VAT on sales, input VAT on purchases, the difference to pay or carry forward, and what was withheld at source.
     *
     * @return array{output:array{base:string,tax:string,count:int},input:array{base:string,tax:string,count:int},net_payable:string,withheld_ait:string,withheld_vat:string,checks:list<array{label:string,register:string,ledger:string,difference:string}>}
     */
    public function worksheet(int $organizationId, string $from, string $to): array
    {
        $sum = function (string $kind) use ($organizationId, $from, $to) {
            $q = TaxEntry::query()->where(['organization_id' => $organizationId, 'kind' => $kind])->whereBetween('entry_date', [$from, $to]);

            return ['base' => bcadd((string) $q->clone()->sum('base_amount'), '0', 2), 'tax' => bcadd((string) $q->clone()->sum('tax_amount'), '0', 2), 'count' => $q->clone()->count()];
        };
        $out = $sum('vat_output');
        $in = $sum('vat_input');
        $ait = $sum('ait');
        $vds = $sum('vds');
        $movement = function (string $role, bool $credit) use ($organizationId, $from, $to) {
            $account = $this->chart->accountFor($organizationId, $role);
            $row = $this->ledger->trialBalance($organizationId, $from, $to)->firstWhere('account_id', $account->id);

            return $row ? ($credit ? $row->credit : $row->debit) : '0.00';
        };
        $checks = [];
        foreach ([['VAT output: register vs ledger credits', $out['tax'], $movement('vat_output', true)], ['VAT input: register vs ledger debits', $in['tax'], $movement('vat_input', false)], ['AIT/TDS withheld: register vs ledger credits', $ait['tax'], $movement('ait_payable', true)]] as [$label, $reg, $led]) {
            $checks[] = ['label' => $label, 'register' => $reg, 'ledger' => $led, 'difference' => bcsub($reg, $led, 2)];
        }

        return ['output' => $out, 'input' => $in, 'net_payable' => bcsub($out['tax'], $in['tax'], 2), 'withheld_ait' => $ait['tax'], 'withheld_vat' => $vds['tax'], 'checks' => $checks];
    }
}
