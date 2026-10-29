<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Billing\BillingCashierSession;
use App\Models\Finance\Account;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankDeposit;
use App\Models\Finance\BankStatement;
use App\Models\Finance\BankStatementLine;
use App\Services\Finance\BankService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bank, till, wallet and gateway accounts; banking a till; moving money; bank statements matched line by line. */
class FinanceBankController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly BankService $banks)
    {
        parent::__construct($tenant);
    }

    public function accounts(Request $request)
    {
        $this->allow($request, 'finance.bank.view');
        $org = $this->organizationId();
        $rows = BankAccount::query()->where('organization_id', $org)->orderBy('kind')->orderBy('name')->get()->map(fn ($b) => ['bank' => $b, 'balance' => $this->banks->ledgerBalance($b)]);

        return view('admin.finance.banks', ['rows' => $rows, 'ledger' => Account::query()->where(['organization_id' => $org, 'type' => 'asset', 'postable' => true, 'active' => true])->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function storeAccount(Request $request)
    {
        $this->allow($request, 'finance.bank.manage');
        $d = $request->validate(['name' => ['required', 'string', 'max:120'], 'kind' => ['required', 'in:bank,cash,wallet,gateway'], 'account_id' => ['required', 'integer'], 'bank_name' => ['nullable', 'string', 'max:120'], 'number_last4' => ['nullable', 'digits_between:0,4'], 'payment_method_code' => ['nullable', 'string', 'max:30']]);
        $this->banks->createAccount($this->organizationId(), $d['name'], $d['kind'], (int) $d['account_id'], $d['bank_name'] ?? null, $d['number_last4'] ?? null, $d['payment_method_code'] ?? null);

        return back()->with('success', __('Account added.'));
    }

    public function deposits(Request $request)
    {
        $this->allow($request, 'finance.bank.view');
        $org = $this->organizationId();
        $banked = BankDeposit::query()->where('organization_id', $org)->whereNotNull('cashier_session_id')->pluck('cashier_session_id');
        $sessions = BillingCashierSession::query()->where('organization_id', $org)->where('status', 'closed')->whereNotIn('id', $banked)->latest('closed_at')->limit(50)->get();

        return view('admin.finance.deposits', ['rows' => BankDeposit::query()->where('organization_id', $org)->with('bank:id,name')->latest('id')->paginate(30), 'sessions' => $sessions, 'accounts' => BankAccount::query()->where(['organization_id' => $org, 'active' => true])->orderBy('name')->get()]);
    }

    public function bankSession(Request $request)
    {
        $this->allow($request, 'finance.bank.manage');
        $d = $request->validate(['session_id' => ['required', 'integer'], 'bank_account_id' => ['required', 'integer'], 'amount' => ['nullable', 'numeric'], 'reference' => ['nullable', 'string', 'max:80']]);
        $org = $this->organizationId();
        $session = BillingCashierSession::query()->where('organization_id', $org)->findOrFail($d['session_id']);
        $bank = BankAccount::query()->where('organization_id', $org)->findOrFail($d['bank_account_id']);
        $this->banks->depositSession($session, $bank, $request->user(), isset($d['amount']) ? (string) $d['amount'] : null, $d['reference'] ?? null);

        return back()->with('success', __('Banked.'));
    }

    public function transfer(Request $request)
    {
        $this->allow($request, 'finance.bank.manage');
        $d = $request->validate(['from_id' => ['required', 'integer'], 'to_id' => ['required', 'integer'], 'amount' => ['required', 'numeric'], 'date' => ['required', 'date'], 'reference' => ['nullable', 'string', 'max:80']]);
        $org = $this->organizationId();
        $this->banks->transfer($org, BankAccount::query()->where('organization_id', $org)->findOrFail($d['from_id']), BankAccount::query()->where('organization_id', $org)->findOrFail($d['to_id']), (string) $d['amount'], $d['date'], $request->user(), $d['reference'] ?? null);

        return back()->with('success', __('Money moved.'));
    }

    public function settleGateway(Request $request)
    {
        $this->allow($request, 'finance.bank.manage');
        $d = $request->validate(['settlement_id' => ['required', 'integer'], 'bank_account_id' => ['required', 'integer']]);
        $org = $this->organizationId();
        $this->banks->settleGateway($org, (int) $d['settlement_id'], BankAccount::query()->where('organization_id', $org)->findOrFail($d['bank_account_id']), $request->user());

        return back()->with('success', __('Settlement booked.'));
    }

    public function statements(Request $request)
    {
        $this->allow($request, 'finance.bank.view');
        $org = $this->organizationId();
        $settlements = DB::table('portal_settlements')->where('organization_id', $org)->whereNotIn('id', DB::table('fin_journals')->where('organization_id', $org)->where('source_type', 'portal_settlement')->pluck('source_id'))->latest('id')->limit(20)->get();

        return view('admin.finance.statements', ['rows' => BankStatement::query()->where('organization_id', $org)->with('bank:id,name')->latest('id')->paginate(20), 'accounts' => BankAccount::query()->where(['organization_id' => $org, 'active' => true])->whereIn('kind', ['bank', 'wallet', 'gateway'])->orderBy('name')->get(), 'settlements' => $settlements]);
    }

    public function importStatement(Request $request)
    {
        $this->allow($request, 'finance.bank.reconcile');
        $d = $request->validate(['bank_account_id' => ['required', 'integer'], 'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'], 'opening' => ['nullable', 'numeric'], 'closing' => ['nullable', 'numeric']]);
        $bank = BankAccount::query()->where('organization_id', $this->organizationId())->findOrFail($d['bank_account_id']);
        $r = $this->banks->importStatement($bank, (string) file_get_contents($request->file('file')->getRealPath()), $request->file('file')->getClientOriginalName(), $request->user(), isset($d['opening']) ? (string) $d['opening'] : null, isset($d['closing']) ? (string) $d['closing'] : null);

        return redirect()->route('admin.finance.statements.show', $r['statement']->public_id)->with('success', __(':n lines imported, :d already known.', ['n' => $r['imported'], 'd' => $r['duplicates']]));
    }

    public function showStatement(Request $request, string $statement)
    {
        $this->allow($request, 'finance.bank.view');
        $s = $this->find(BankStatement::class, $statement);

        return view('admin.finance.statement', ['s' => $s, 'bank' => BankAccount::query()->find($s->bank_account_id), 'summary' => $this->banks->summary($s), 'lines' => BankStatementLine::query()->where('statement_id', $s->id)->orderBy('txn_date')->orderBy('id')->get(),
            'accounts' => Account::query()->where(['organization_id' => $this->organizationId(), 'active' => true, 'postable' => true])->orderBy('code')->get(['code', 'name'])]);
    }

    public function actStatement(Request $request, string $statement)
    {
        $this->allow($request, 'finance.bank.reconcile');
        $s = $this->find(BankStatement::class, $statement);
        $d = $request->validate(['action' => ['required', 'in:auto,post,ignore,done'], 'line_id' => ['nullable', 'integer'], 'account' => ['nullable', 'string', 'max:20'], 'note' => ['nullable', 'string', 'max:250']]);
        if ($d['action'] === 'auto') {
            $n = $this->banks->autoMatch($s, $request->user());

            return back()->with('success', __(':n lines matched.', ['n' => $n]));
        }
        if ($d['action'] === 'done') {
            $this->banks->markReconciled($s, $request->user());

            return back()->with('success', __('Statement reconciled.'));
        }
        $line = BankStatementLine::query()->where('statement_id', $s->id)->findOrFail($d['line_id'] ?? 0);
        $d['action'] === 'post' ? $this->banks->postLine($line, (string) ($d['account'] ?? ''), $request->user(), $d['note'] ?? null) : $this->banks->ignoreLine($line, $request->user(), (string) ($d['note'] ?? ''));

        return back()->with('success', __('Done.'));
    }
}
