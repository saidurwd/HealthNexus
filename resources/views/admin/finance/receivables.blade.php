@extends('layouts.adminlte')

@section('page_title', __('Who owes us'))

@section('page_content')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="d-flex gap-2 mb-3 align-items-center">
        @foreach([null => 'Everyone', 'patient' => 'Patients', 'corporate' => 'Corporate clients', 'payer' => 'Insurers'] as $k => $label)<a class="btn btn-sm {{ $type === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.finance.receivables', array_filter(['type' => $k])) }}">{{ __($label) }}</a>@endforeach
        @if(auth()->user()->can('finance.report.export'))<a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a>@endif
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.finance.writeoffs') }}">{{ __('Write-offs') }}</a></div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Who') }}</th>@foreach($age['buckets'] as $b)<th class="text-end">{{ __($b) }}</th>@endforeach<th class="text-end">{{ __('Total') }}</th></tr></thead><tbody>
        @forelse($age['rows'] as $r)<tr><td><a href="{{ route('admin.finance.receivables.statement', [$r['party_type'], $r['party_id']]) }}">{{ $r['name'] }}</a> <small class="text-muted">{{ __(\Illuminate\Support\Str::headline($r['party_type'])) }}</small></td>@foreach($r['buckets'] as $v)<td class="text-end">{{ bccomp($v, '0', 2) ? $m($v) : '' }}</td>@endforeach<td class="text-end fw-bold">{{ $m($r['total']) }}</td></tr>@empty<tr><td colspan="{{ count($age['buckets']) + 2 }}" class="text-muted">{{ __('Nothing is owed.') }}</td></tr>@endforelse</tbody>
        <tfoot><tr class="fw-bold"><td>{{ __('Total') }}</td>@foreach($age['totals']['buckets'] as $v)<td class="text-end">{{ $m($v) }}</td>@endforeach<td class="text-end">{{ $m($age['totals']['total']) }}</td></tr></tfoot></table></div></div>
@stop
