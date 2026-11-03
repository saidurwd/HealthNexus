<?php

namespace Modules\Finance\Http\Controllers\Web;

use App\Models\Finance\Account;
use App\Models\Finance\CostCenter;
use App\Models\Finance\Journal;
use App\Services\Finance\JournalService;
use App\Services\TenantContextResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Journals: write, send for approval, approve or send back, reverse. */
class FinanceJournalController extends FinanceController
{
    public function __construct(TenantContextResolver $tenant, private readonly JournalService $journals)
    {
        parent::__construct($tenant);
    }

    public function index(Request $request)
    {
        $this->allow($request, 'finance.journal.view');
        [$from, $to] = $this->range($request);
        $rows = Journal::query()->where('organization_id', $this->organizationId())->whereBetween('journal_date', [$from, $to])->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->when($request->query('kind'), fn ($q, $v) => $q->where('kind', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('number', 'like', "%$v%")->orWhere('memo', 'like', "%$v%")->orWhere('reference', 'like', "%$v%")))->latest('journal_date')->latest('id')->paginate(30)->withQueryString();

        return view('admin.finance.journals', ['rows' => $rows, 'from' => $from, 'to' => $to]);
    }

    public function create(Request $request)
    {
        $this->allow($request, 'finance.journal.create');
        $org = $this->organizationId();

        return view('admin.finance.journal-form', ['accounts' => Account::query()->where(['organization_id' => $org, 'active' => true, 'postable' => true])->orderBy('code')->get(['id', 'code', 'name', 'control']), 'centers' => CostCenter::query()->where(['organization_id' => $org, 'active' => true])->orderBy('code')->get(['id', 'code', 'name'])]);
    }

    public function store(Request $request)
    {
        $this->allow($request, 'finance.journal.create');
        $d = $request->validate(['date' => ['required', 'date'], 'memo' => ['required', 'string', 'max:250'], 'reference' => ['nullable', 'string', 'max:80'], 'lines' => ['required', 'array', 'min:2', 'max:200'], 'lines.*.account' => ['nullable', 'integer'], 'lines.*.debit' => ['nullable', 'numeric'], 'lines.*.credit' => ['nullable', 'numeric'],
            'lines.*.cost_center' => ['nullable', 'integer'], 'lines.*.memo' => ['nullable', 'string', 'max:200'], 'lines.*.party_type' => ['nullable', 'in:patient,corporate,payer,supplier,employee'], 'lines.*.party_id' => ['nullable', 'integer'], 'submit' => ['nullable', 'boolean']]);
        $lines = collect($d['lines'])->filter(fn ($l) => ! empty($l['account']))->map(fn ($l) => ['account' => (int) $l['account'], 'debit' => (string) ($l['debit'] ?: '0'), 'credit' => (string) ($l['credit'] ?: '0'), 'cost_center' => $l['cost_center'] ?? null, 'memo' => $l['memo'] ?? null,
            'party_type' => ($l['party_type'] ?? null) ?: null, 'party_id' => ($l['party_id'] ?? null) ?: null])->values()->all();
        $journal = $this->journals->createDraft($this->organizationId(), ['date' => $d['date'], 'memo' => $d['memo'], 'reference' => $d['reference'] ?? null], $lines, $request->user());
        if ($request->boolean('submit')) {
            try {
                $journal = $this->journals->submit($journal, $request->user());
            } catch (ValidationException $e) {
                return redirect()->route('admin.finance.journals.show', $journal->public_id)->withErrors($e->errors());
            }
        }

        return redirect()->route('admin.finance.journals.show', $journal->public_id)->with('success', $journal->status === 'posted' ? __('Journal posted.') : ($journal->status === 'pending' ? __('Sent for approval.') : __('Saved as a draft.')));
    }

    public function show(Request $request, string $journal)
    {
        $this->allow($request, 'finance.journal.view');
        $j = $this->find(Journal::class, $journal);

        return view('admin.finance.journal', ['j' => $j, 'lines' => $j->lines()->with('account:id,code,name')->get(), 'reversal' => $j->reversed_by_id ? Journal::query()->find($j->reversed_by_id) : null, 'original' => $j->reversal_of_id ? Journal::query()->find($j->reversal_of_id) : null,
            'centers' => CostCenter::query()->where('organization_id', $this->organizationId())->pluck('code', 'id')]);
    }

    public function act(Request $request, string $journal)
    {
        $j = $this->find(Journal::class, $journal);
        $d = $request->validate(['action' => ['required', 'in:submit,approve,reject,reverse,discard'], 'note' => ['nullable', 'string', 'max:250']]);
        $needs = ['submit' => 'finance.journal.create', 'discard' => 'finance.journal.create', 'approve' => 'finance.journal.approve', 'reject' => 'finance.journal.approve', 'reverse' => 'finance.journal.reverse'][$d['action']];
        $this->allow($request, $needs);
        $by = $request->user();
        try {
            match ($d['action']) {
                'submit' => $this->journals->submit($j, $by),
                'approve' => $this->journals->approve($j, $by),
                'reject' => $this->journals->reject($j, $by, (string) ($d['note'] ?? '')),
                'reverse' => $this->journals->reverse($j, $by, (string) ($d['note'] ?? '')),
                'discard' => $this->journals->discard($j),
                default => abort(422),
            };
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return $d['action'] === 'discard' ? redirect()->route('admin.finance.journals')->with('success', __('Draft deleted.')) : back()->with('success', __('Done.'));
    }
}
