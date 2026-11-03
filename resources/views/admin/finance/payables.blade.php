@extends('layouts.adminlte')

@section('page_title', __('What we owe suppliers'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="d-flex gap-2 mb-3">@can('finance.payable.manage')<a class="btn btn-primary" href="{{ route('admin.finance.payments.create') }}">{{ __('Pay suppliers') }}</a>@endcan <a class="btn btn-outline-secondary" href="{{ route('admin.finance.payments') }}">{{ __('Supplier payments') }}</a>
        @if(auth()->user()->can('finance.report.export'))<a class="btn btn-outline-secondary ms-auto" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a>@endif</div>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Ageing by supplier') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Supplier') }}</th>@foreach($age['buckets'] as $b)<th class="text-end">{{ __($b) }}</th>@endforeach<th class="text-end">{{ __('Total') }}</th></tr></thead><tbody>
        @forelse($age['rows'] as $r)<tr><td>{{ $r['name'] }}</td>@foreach($r['buckets'] as $v)<td class="text-end">{{ bccomp($v, '0', 2) ? $m($v) : '' }}</td>@endforeach<td class="text-end fw-bold">{{ $m($r['total']) }}</td></tr>@empty<tr><td colspan="7" class="text-muted">{{ __('Nothing is owed.') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Open supplier invoices') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Supplier') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Still owed') }}</th></tr></thead><tbody>
        @foreach($open as $p)<tr><td>{{ $suppliers[$p->supplier_id] ?? '#'.$p->supplier_id }}</td><td>{{ $p->invoice_number }}</td><td>{{ $p->invoice_date->format('d M Y') }}</td><td>{{ $p->due_date?->format('d M Y') }}</td><td class="text-end">{{ $m($p->amount) }}</td><td class="text-end">{{ $m($svc->outstanding($p)) }}</td></tr>@endforeach</tbody></table></div></div>
@stop
