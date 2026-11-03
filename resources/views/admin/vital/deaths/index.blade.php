@extends('layouts.adminlte')

@section('page_title', __('Death certificates'))

@section('page_content')
    @include('admin.vital._flash')
    @if($awaitingTotal > 0)
        <div class="card border-danger mb-3"><div class="card-header"><h3 class="card-title">{{ __('Deaths with no certificate yet') }} ({{ $awaitingTotal }})</h3></div>
            <ul class="list-group list-group-flush small">
                @foreach($awaiting as $a)@php $p = $names[$a->patient_id] ?? null; @endphp
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $p?->full_name }} <span class="text-muted">{{ $p?->enterprise_patient_no }}</span> · {{ \Carbon\CarbonImmutable::parse($a->died_at, 'UTC')->setTimezone('Asia/Dhaka')->format('d M Y, H:i') }}</span>
                        @if($can['create'])<a class="btn btn-sm btn-outline-danger" href="{{ route('admin.vital.deaths.create', ['patient_id' => $a->patient_id]) }}">{{ __('Write the certificate') }}</a>@endif</li>
                @endforeach
            </ul></div>
    @endif
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['' => 'All', 'draft' => 'Waiting for a doctor', 'certified' => 'Certified', 'issued' => 'Issued', 'cancelled' => 'Cancelled'] as $k => $label)
            <a class="btn btn-sm {{ (string) request('status', '') === (string) $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.vital.deaths', $k ? ['status' => $k] : []) }}">{{ __($label) }}</a>
        @endforeach
        @if($can['create'])<a class="btn btn-sm btn-success ms-auto" href="{{ route('admin.vital.deaths.create') }}"><i class="bi bi-plus-lg"></i> {{ __('Write a certificate') }}</a>@endif
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0" data-list-count="{{ $rows->total() }}">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Died') }}</th><th>{{ __('Manner') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse($rows as $c)
            <tr><td><a href="{{ route('admin.vital.deaths.show', $c->public_id) }}">{{ $c->number }}</a></td><td>{{ $c->patient?->full_name }} <small class="text-muted">{{ $c->patient?->enterprise_patient_no }}</small></td>
                <td>{{ $c->died_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->manner)) }}@if($c->medico_legal) <i class="bi bi-shield-exclamation text-warning" title="{{ __('Police case') }}"></i>@endif</td><td><x-ui.status-badge :status="$c->status" :label="__(\Illuminate\Support\Str::headline($c->status))" /></td></tr>
        @empty
            <tr><td colspan="5"><x-ui.empty-state :title="__('No death certificate here.')" icon="bi-file-earmark-medical" /></td></tr>
        @endforelse
        </tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
