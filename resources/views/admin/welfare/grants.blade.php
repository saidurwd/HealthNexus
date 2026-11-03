@extends('layouts.adminlte')

@section('page_title', __('Welfare grants'))

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex gap-2 mb-2">@foreach(['requested', 'approved', 'used', 'rejected', 'expired', 'cancelled', 'all'] as $s)<a class="btn btn-sm {{ $status === $s ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.welfare.grants', ['status' => $s]) }}">{{ $s === 'all' ? __('All') : $H($s) }}</a>@endforeach</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Fund') }}</th><th class="text-end">{{ __('Limit') }}</th><th class="text-end">{{ __('Given') }}</th><th>{{ __('Status') }}</th><th>{{ __('Until') }}</th></tr></thead><tbody>
        @forelse($rows as $g)<tr><td><a href="{{ route('admin.welfare.grants.show', $g->public_id) }}">{{ $g->number }}</a></td><td>{{ $g->patient?->display_name }} <span class="text-muted">{{ $g->patient?->enterprise_patient_no }}</span></td><td>{{ $g->fund?->name }}</td><td class="text-end">{{ number_format((float) $g->cap_amount, 2) }}</td><td class="text-end">{{ number_format((float) $g->spent_amount, 2) }}</td><td><x-ui.status-badge :status="$g->status" :label="$H($g->status)" /></td><td>{{ $g->valid_to?->format('d M Y') }}</td></tr>@empty<tr><td colspan="7" class="text-muted">{{ __('Nothing here.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
