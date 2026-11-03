@extends('layouts.adminlte')

@section('page_title', __('Settlement file'))

@section('page_content')
    @include('admin.portal._flash')
    <div class="card"><div class="card-body">{{ $s->gateway }} · {{ $s->period_from->toDateString() }} → {{ $s->period_to->toDateString() }} · {{ $s->file_name }} · <strong>{{ $s->line_count }}</strong> {{ __('lines') }}, {{ __('total') }} {{ number_format((float) $s->total, 2) }}, {{ __('fees') }} {{ number_format((float) $s->fees, 2) }} · <span class="text-success">{{ $s->matched }} {{ __('matched') }}</span> · <span class="{{ $s->differences ? 'text-danger' : '' }}">{{ $s->differences }} {{ __('need a look') }}</span></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Lines') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Reference') }}</th><th class="text-end">{{ __('Amount in the file') }}</th><th class="text-end">{{ __('Fee') }}</th><th>{{ __('Result') }}</th></tr></thead>
        <tbody>@foreach($lines as $l)<tr><td>{{ $l->gateway_ref }}</td><td class="text-end">{{ number_format((float) $l->amount, 2) }}</td><td class="text-end">{{ number_format((float) $l->fee, 2) }}</td><td><x-ui.status-badge :status="$l->result" /></td></tr>@endforeach</tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Paid online in these dates but missing from the file') }}</h3></div><ul class="list-group list-group-flush small">@forelse($missing as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ $p->gateway_ref }} · {{ $p->completed_at?->format('d M H:i') }}</span><strong>{{ number_format((float) $p->amount, 2) }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div>
@stop
