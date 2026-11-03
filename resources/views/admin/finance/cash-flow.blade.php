@extends('layouts.adminlte')

@section('page_title', __('Cash flow'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><tbody>
        @foreach(['operating' => 'Operating activities', 'investing' => 'Investing activities (equipment)', 'financing' => 'Financing activities (capital)'] as $key => $title)
            <tr class="table-light fw-bold"><td colspan="2">{{ __($title) }}</td></tr>
            @foreach($r['sections'][$key] as $i)<tr><td>{{ __($i['label']) }}</td><td class="text-end">{{ $m($i['amount']) }}</td></tr>@endforeach
            <tr class="fw-bold"><td>{{ __('Net') }}</td><td class="text-end">{{ $m($r[$key]) }}</td></tr>
        @endforeach
        <tr class="fw-bold"><td>{{ __('Change in cash and bank') }}</td><td class="text-end">{{ $m($r['net_change']) }}</td></tr>
        <tr><td>{{ __('Cash and bank at the start') }}</td><td class="text-end">{{ $m($r['opening_cash']) }}</td></tr>
        <tr><td>{{ __('Cash and bank at the end') }}</td><td class="text-end">{{ $m($r['closing_cash']) }}</td></tr></tbody></table></div></div>
    <p class="mt-3 fw-bold {{ $r['reconciles'] ? 'text-success' : 'text-danger' }}">{{ $r['reconciles'] ? __('This explains the whole change in cash and bank.') : __('This does not explain the change in cash and bank.') }}</p>
@stop
