@extends('layouts.adminlte')

@section('page_title', __('Finance and accounts'))

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @if(! $d['ready'])
        <div class="alert alert-info">{{ __('The books are not started yet. Make the starter chart of accounts, then create the first fiscal year. Until then nothing from Billing is posted.') }}
            @can('finance.chart.manage')<form method="POST" action="{{ route('admin.finance.accounts.start') }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary ms-2">{{ __('Make the starter chart') }}</button></form>@endcan
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.finance.years') }}">{{ __('Fiscal years') }}</a></div>
    @endif
    @php($tiles = [
        [__('Cash and bank (books)'), $m($d['cash_total']), false, route('admin.finance.banks')],
        [__('Owed to us (receivables)'), $m($d['receivable']), false, route('admin.finance.receivables')],
        [__('We owe suppliers (payables)'), $m($d['payable']), false, route('admin.finance.payables')],
        [__('Revenue this month'), $m($d['revenue']), false, route('admin.finance.income-statement')],
        [__('Expenses this month'), $m($d['expense']), false, route('admin.finance.income-statement')],
        [__('Result this month'), $m($d['net']), bccomp($d['net'], '0', 2) < 0, route('admin.finance.income-statement')],
        [__('Postings that failed'), $d['failed'], $d['failed'] > 0, route('admin.finance.postings')],
        [__('Journals waiting for approval'), $d['pending'], false, route('admin.finance.journals', ['status' => 'pending'])],
        [__('Bank lines not matched'), $d['unmatched_lines'], $d['unmatched_lines'] > 0, route('admin.finance.statements')],
    ])
    <div class="row g-3 mb-3">@foreach($tiles as [$label, $value, $warn, $url])<div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 {{ $warn ? 'text-danger' : '' }}"><a href="{{ $url }}" class="text-reset text-decoration-none">{{ $value }}</a></div></div></div></div>@endforeach</div>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Cash, bank and wallets') }}</h3></div><ul class="list-group list-group-flush small">@forelse($d['banks'] as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b['bank']->name }}</span><strong>{{ $m($b['balance']) }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('No accounts yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Billing against the books') }} — {{ $d['month'] }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th></th><th class="text-end">{{ __('Billing') }}</th><th class="text-end">{{ __('Books') }}</th><th class="text-end">{{ __('Difference') }}</th></tr></thead><tbody>
            @foreach($d['recon']['rows'] as $r)<tr><td>{{ __($r['label']) }}</td><td class="text-end">{{ $m($r['billing']) }}</td><td class="text-end">{{ $m($r['ledger']) }}</td><td class="text-end {{ bccomp($r['difference'], '0', 2) !== 0 ? 'text-danger' : '' }}">{{ $m($r['difference']) }}</td></tr>@endforeach</tbody></table></div>
            <div class="card-footer small">{{ $d['recon']['in_balance'] ? __('Billing and the books agree.') : __('Something differs; open the check for the reason.') }} <a href="{{ route('admin.finance.reconcile') }}">{{ __('Open the check') }}</a></div></div></div>
    </div>
    @if($d['period'])<p class="text-muted small mt-3">{{ __('Current period') }}: {{ $d['period']->name }} — <x-ui.status-badge :status="$d['period']->status" /></p>@endif
@stop
