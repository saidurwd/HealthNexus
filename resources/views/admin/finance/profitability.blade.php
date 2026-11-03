@extends('layouts.adminlte')

@section('page_title', __('Profit by department'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    @include('admin.finance._range', ['from' => $from, 'to' => $to, 'export' => true])
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Cost centre') }}</th><th>{{ __('Code') }}</th><th class="text-end">{{ __('Revenue') }}</th><th class="text-end">{{ __('Expense') }}</th><th class="text-end">{{ __('Result') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td>{{ $r['cost_center'] }}</td><td>{{ $r['code'] }}</td><td class="text-end">{{ $m($r['revenue']) }}</td><td class="text-end">{{ $m($r['expense']) }}</td><td class="text-end {{ bccomp($r['result'], '0', 2) < 0 ? 'text-danger' : '' }}">{{ $m($r['result']) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('Nothing posted in this range.') }}</td></tr>@endforelse</tbody></table></div></div>
@stop
