@extends('layouts.adminlte')

@section('page_title', __('Police cases'))

@section('page_content')
    @include('admin.vital._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['' => 'All', 'open' => 'Police not yet told', 'police_informed' => 'Police told', 'postmortem_requested' => 'Post-mortem asked for', 'closed' => 'Closed'] as $k => $label)
            <a class="btn btn-sm {{ (string) request('status', '') === (string) $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.vital.medicolegal', $k ? ['status' => $k] : []) }}">{{ __($label) }}</a>
        @endforeach
        @if($can['register'])<a class="btn btn-sm btn-success ms-auto" href="{{ route('admin.vital.medicolegal.create') }}"><i class="bi bi-plus-lg"></i> {{ __('Register a police case') }}</a>@endif
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0" data-list-count="{{ $rows->total() }}">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Brought') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse($rows as $c)
            <tr class="{{ in_array($c->id, $late, true) ? 'table-danger' : '' }}"><td><a href="{{ route('admin.vital.medicolegal.show', $c->public_id) }}">{{ $c->number }}</a></td><td>{{ $c->patient?->full_name }} <small class="text-muted">{{ $c->patient?->enterprise_patient_no }}</small></td>
                <td>{{ __(\Illuminate\Support\Str::headline($c->kind)) }}</td><td>{{ $c->brought_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</td>
                <td><x-ui.status-badge :status="$c->status" :label="__(\Illuminate\Support\Str::headline($c->status))" />@if(in_array($c->id, $late, true)) <span class="badge text-bg-danger">{{ __('Police not told in time') }}</span>@endif</td></tr>
        @empty
            <tr><td colspan="5"><x-ui.empty-state :title="__('No police case here.')" icon="bi-shield-exclamation" /></td></tr>
        @endforelse
        </tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
