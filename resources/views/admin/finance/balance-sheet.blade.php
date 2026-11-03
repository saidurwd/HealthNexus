@extends('layouts.adminlte')

@section('page_title', __('Balance sheet'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-auto"><label class="form-label" for="on">{{ __('As of') }}</label><input type="date" id="on" name="on" value="{{ $on }}" class="form-control"></div><div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button></div>
        @if(auth()->user()->can('finance.report.export'))<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a></div>@endif</div></form>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Assets') }}</h3></div><table class="table table-sm mb-0"><tbody>@foreach($r['assets'] as $x)<tr><td>{{ $x->code }} {{ $x->name }}</td><td class="text-end">{{ $m($x->amount) }}</td></tr>@endforeach<tr class="fw-bold"><td>{{ __('Total assets') }}</td><td class="text-end">{{ $m($r['total_assets']) }}</td></tr></tbody></table></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Liabilities and equity') }}</h3></div><table class="table table-sm mb-0"><tbody>
            @foreach($r['liabilities'] as $x)<tr><td>{{ $x->code }} {{ $x->name }}</td><td class="text-end">{{ $m($x->amount) }}</td></tr>@endforeach<tr class="fw-bold"><td>{{ __('Total liabilities') }}</td><td class="text-end">{{ $m($r['total_liabilities']) }}</td></tr>
            @foreach($r['equity'] as $x)<tr><td>{{ $x->code }} {{ $x->name }}</td><td class="text-end">{{ $m($x->amount) }}</td></tr>@endforeach
            <tr><td>{{ __('Result of the year so far (not yet closed)') }}</td><td class="text-end">{{ $m($r['current_result']) }}</td></tr>
            <tr class="fw-bold"><td>{{ __('Total liabilities and equity') }}</td><td class="text-end">{{ $m(bcadd(bcadd($r['total_liabilities'], $r['total_equity'], 2), $r['current_result'], 2)) }}</td></tr></tbody></table></div></div>
    </div>
    <p class="mt-3 fw-bold {{ $r['balanced'] ? 'text-success' : 'text-danger' }}">{{ $r['balanced'] ? __('The balance sheet balances.') : __('The balance sheet does not balance. Call the accountant.') }}</p>
@stop
