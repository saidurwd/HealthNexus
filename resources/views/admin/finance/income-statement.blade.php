@extends('layouts.adminlte')

@section('page_title', __('Income statement'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true, 'extra' => view('admin.finance._cc', ['centers' => $centers, 'cc' => $cc])->render()])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><tbody>
        <tr class="table-light fw-bold"><td colspan="3">{{ __('Revenue') }}</td></tr>
        @foreach($r['revenue'] as $x)<tr><td>{{ $x->code }}</td><td>{{ $x->name }}</td><td class="text-end">{{ $m($x->amount) }}</td></tr>@endforeach
        <tr class="fw-bold"><td colspan="2">{{ __('Total revenue') }}</td><td class="text-end">{{ $m($r['total_revenue']) }}</td></tr>
        <tr class="table-light fw-bold"><td colspan="3">{{ __('Expenses') }}</td></tr>
        @foreach($r['expense'] as $x)<tr><td>{{ $x->code }}</td><td>{{ $x->name }}</td><td class="text-end">{{ $m($x->amount) }}</td></tr>@endforeach
        <tr class="fw-bold"><td colspan="2">{{ __('Total expenses') }}</td><td class="text-end">{{ $m($r['total_expense']) }}</td></tr>
        <tr class="fw-bold {{ bccomp($r['net'], '0', 2) < 0 ? 'text-danger' : 'text-success' }}"><td colspan="2">{{ bccomp($r['net'], '0', 2) < 0 ? __('Loss for the period') : __('Profit for the period') }}</td><td class="text-end">{{ $m($r['net']) }}</td></tr></tbody></table></div></div>
@stop
