@extends('layouts.adminlte')

@section('page_title', __('Birth notifications'))

@section('page_content')
    @include('admin.vital._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['' => 'All', 'draft' => 'Waiting for a second person', 'issued' => 'Issued', 'registered' => 'Registered', 'cancelled' => 'Cancelled'] as $k => $label)
            <a class="btn btn-sm {{ (string) request('status', '') === (string) $k && ! request('followup') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.vital.births', $k ? ['status' => $k] : []) }}">{{ __($label) }}</a>
        @endforeach
        <a class="btn btn-sm {{ request('followup') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.vital.births', ['followup' => 1]) }}">{{ __('To follow up') }}</a>
        @if($can['create'])<a class="btn btn-sm btn-success ms-auto" href="{{ route('admin.vital.births.create') }}"><i class="bi bi-plus-lg"></i> {{ __('Notify a birth') }}</a>@endif
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0" data-list-count="{{ $rows->total() }}">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Mother') }}</th><th>{{ __('Born') }}</th><th>{{ __('Outcome') }}</th><th>{{ __('Status') }}</th><th>{{ __('Registrar\'s number') }}</th></tr></thead>
        <tbody>
        @forelse($rows as $n)
            <tr><td><a href="{{ route('admin.vital.births.show', $n->public_id) }}">{{ $n->number }}</a></td><td>{{ $n->mother?->full_name }} <small class="text-muted">{{ $n->mother?->enterprise_patient_no }}</small></td>
                <td>{{ $n->born_at->timezone('Asia/Dhaka')->format('d M Y, H:i') }}</td><td>{{ $n->outcome === 'stillbirth' ? __('Stillbirth') : __('Live birth') }}</td><td><x-ui.status-badge :status="$n->status" :label="__(\Illuminate\Support\Str::headline($n->status))" /></td><td>{{ $n->registrar_reference ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="6"><x-ui.empty-state :title="__('No birth notification here.')" icon="bi-balloon-heart" /></td></tr>
        @endforelse
        </tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
