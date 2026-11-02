@extends('layouts.adminlte')

@section('page_title', __('Finance and accounts'))

@section('page_content')
    @include('admin.finance._flash')
    <x-ui.dashboard :question="__('Are the books in order, and how is the month going against last month?')" :audience="__('For the finance manager and the director')" :as-of="\Carbon\CarbonImmutable::now()">
    @php $m = fn ($v) => number_format((float) $v, 2); @endphp
    @if(! $d['ready'])
        <div class="alert alert-info">{{ __('The books are not started yet. Make the starter chart of accounts, then create the first fiscal year. Until then nothing from Billing is posted.') }}
            @can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.accounts.start') }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary ms-2">{{ __('Make the starter chart') }}</button></form>@endcan
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.finance.years') }}">{{ __('Fiscal years') }}</a></div>
    @endif
    @php $L = fn ($route, $perm) => \App\Support\Dashboard\Link::to($route, $perm); @endphp
    <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
        <x-ui.kpi-tile :label="__('Cash and bank (books)')" :value="(float) $d['cash_total']" unit="taka" icon="bi-bank" variant="success" :href="$L('admin.finance.banks', 'finance.bank.view')" good-direction="up" />
        <x-ui.kpi-tile :label="__('Owed to us (receivables)')" :value="(float) $d['receivable']" unit="taka" icon="bi-arrow-down-left-circle" variant="warning" :href="$L('admin.finance.receivables', 'finance.receivable.view')" good-direction="down" />
        <x-ui.kpi-tile :label="__('We owe suppliers (payables)')" :value="(float) $d['payable']" unit="taka" icon="bi-arrow-up-right-circle" variant="warning" :href="$L('admin.finance.payables', 'finance.payable.view')" good-direction="down" />
        <x-ui.kpi-tile :label="__('Revenue this month')" :value="(float) $d['revenue']" :previous="(float) $d['revenue_before']" unit="taka" icon="bi-graph-up-arrow" variant="primary" :href="$L('admin.finance.income-statement', 'finance.report.view')" good-direction="up" :compare-label="__('same days last month')" />
        <x-ui.kpi-tile :label="__('Expenses this month')" :value="(float) $d['expense']" :previous="(float) $d['expense_before']" unit="taka" icon="bi-graph-down-arrow" variant="secondary" :href="$L('admin.finance.income-statement', 'finance.report.view')" good-direction="down" :compare-label="__('same days last month')" />
        <x-ui.kpi-tile :label="__('Result this month')" :value="(float) $d['net']" :previous="(float) $d['net_before']" unit="taka" icon="bi-calculator" variant="info" :href="$L('admin.finance.income-statement', 'finance.report.view')" good-direction="up" :compare-label="__('same days last month')" />
        <x-ui.kpi-tile :label="__('Postings that failed')" :value="$d['failed']" unit="count" icon="bi-exclamation-triangle" variant="danger" :href="$L('admin.finance.postings', 'finance.posting.view')" good-direction="down" />
        <x-ui.kpi-tile :label="__('Journals waiting for approval')" :value="$d['pending']" unit="count" icon="bi-journal-check" variant="warning" :href="$L('admin.finance.journals', 'finance.journal.view')" good-direction="down" />
        <x-ui.kpi-tile :label="__('Bank lines not matched')" :value="$d['unmatched_lines']" unit="count" icon="bi-link-45deg" variant="warning" :href="$L('admin.finance.statements', 'finance.bank.view')" good-direction="down" />
    </div>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Cash, bank and wallets') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['banks'] as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b['bank']->name }}</span><strong>{{ $m($b['balance']) }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('No accounts yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Billing against the books') }} — {{ $d['month'] }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th></th><th class="text-end">{{ __('Billing') }}</th><th class="text-end">{{ __('Books') }}</th><th class="text-end">{{ __('Difference') }}</th></tr></thead><tbody>
            @foreach($d['recon']['rows'] as $r)<tr><td>{{ __($r['label']) }}</td><td class="text-end">{{ $m($r['billing']) }}</td><td class="text-end">{{ $m($r['ledger']) }}</td><td class="text-end {{ bccomp($r['difference'], '0', 2) !== 0 ? 'text-danger' : '' }}">{{ $m($r['difference']) }}</td></tr>@endforeach</tbody></table></div>
            <div class="card-footer small">{{ $d['recon']['in_balance'] ? __('Billing and the books agree.') : __('Something differs; open the check for the reason.') }} <a href="{{ route('admin.finance.reconcile') }}">{{ __('Open the check') }}</a></div></div></div>
    </div>
    @if($d['period'])<p class="text-muted small mt-3">{{ __('Current period') }}: {{ $d['period']->name }} — <x-ui.status-badge :status="$d['period']->status" /></p>@endif
    </x-ui.dashboard>
@stop
