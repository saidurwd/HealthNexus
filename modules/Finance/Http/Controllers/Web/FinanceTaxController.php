<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Account;
use App\Models\Finance\TaxCode;
use App\Services\Finance\TaxService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;

/** Tax codes (rates are the accountant's setting), the VAT/AIT register and the return worksheet. Nothing here is tax advice. */
class FinanceTaxController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly TaxService $tax)
    {
        parent::__construct($tenant);
    }

    public function codes(Request $request)
    {
        $this->allow($request, 'finance.tax.view');
        $org = $this->organizationId();

        return view('admin.finance.tax-codes', ['rows' => TaxCode::query()->where('organization_id', $org)->with('account:id,code,name')->orderBy('code')->get(), 'kinds' => TaxService::KINDS, 'accounts' => Account::query()->where(['organization_id' => $org, 'active' => true, 'postable' => true])->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function saveCode(Request $request)
    {
        $this->allow($request, 'finance.tax.manage');
        $d = $request->validate(['id' => ['nullable', 'integer'], 'code' => ['required', 'string', 'max:20'], 'name' => ['required', 'string', 'max:120'], 'kind' => ['required', 'string'], 'rate' => ['required', 'numeric'], 'account_id' => ['required', 'integer'], 'note' => ['nullable', 'string', 'max:200']]);
        $existing = ! empty($d['id']) ? TaxCode::query()->where('organization_id', $this->organizationId())->findOrFail($d['id']) : null;
        $this->tax->saveCode($this->organizationId(), $d, $existing);

        return back()->with('success', __('Saved.'));
    }

    public function register(Request $request)
    {
        $this->allow($request, 'finance.tax.view');
        [$from, $to] = $this->range($request);
        $kind = in_array($request->query('kind'), TaxService::KINDS, true) ? $request->query('kind') : 'vat_output';
        $rows = $this->tax->register($this->organizationId(), $kind, $from, $to);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'tax-register-'.$kind, ['Date', 'Document', 'Party', 'Tax id', 'Base', 'Tax'], $rows->map(fn ($r) => [$r->entry_date->toDateString(), $r->document_number, $r->party_name, $r->party_tax_id, $r->base_amount, $r->tax_amount]));
        }

        return view('admin.finance.tax-register', ['rows' => $rows, 'kind' => $kind, 'kinds' => TaxService::KINDS, 'from' => $from, 'to' => $to]);
    }

    public function worksheet(Request $request)
    {
        $this->allow($request, 'finance.tax.view');
        [$from, $to] = $this->range($request);
        $w = $this->tax->worksheet($this->organizationId(), $from, $to);
        if ($request->query('export') === 'csv') {
            return $this->csv($request, 'vat-worksheet', ['Line', 'Base', 'Tax'], [['Output VAT (sales)', $w['output']['base'], $w['output']['tax']], ['Input VAT (purchases)', $w['input']['base'], $w['input']['tax']], ['Net VAT payable (output - input)', '', $w['net_payable']], ['AIT/TDS withheld', '', $w['withheld_ait']], ['VAT withheld at source', '', $w['withheld_vat']]]);
        }

        return view('admin.finance.tax-worksheet', ['w' => $w, 'from' => $from, 'to' => $to]);
    }
}
