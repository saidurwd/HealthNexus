@extends('layouts.adminlte')

@section('page_title', __('Pregnancies'))

@section('page_content')
    @include('admin.mch._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['active' => 'Being followed', 'delivered' => 'Delivered', 'ended' => 'Closed', 'all' => 'All'] as $k => $label)
            <a class="btn btn-sm {{ $status === $k && ! request('due') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.mch.pregnancies', ['status' => $k]) }}">{{ __($label) }}</a>
        @endforeach
        <a class="btn btn-sm {{ request('due') ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.mch.pregnancies', ['due' => 1]) }}">{{ __('Due within 30 days') }}</a>
        @if($can['book'])<a class="btn btn-sm btn-success ms-auto" href="{{ route('admin.mch.pregnancies.create') }}"><i class="bi bi-plus-lg"></i> {{ __('Book a pregnancy') }}</a>@endif
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0" data-list-count="{{ $rows->total() }}">
        <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Woman') }}</th><th>{{ __('Expected date') }}</th><th class="text-end">{{ __('Visits') }}</th><th>{{ __('Risk') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @forelse($rows as $p)
            <tr><td><a href="{{ route('admin.mch.pregnancies.show', $p->public_id) }}">{{ $p->number }}</a></td><td>{{ $p->patient?->full_name }} <small class="text-muted">{{ $p->patient?->enterprise_patient_no }}</small></td>
                <td>{{ $p->edd->format('d M Y') }}</td><td class="text-end">{{ $p->visits_count }}</td><td>@if($p->risk_factors)<span class="badge text-bg-danger">{{ __('High risk') }}</span>@endif</td><td><x-ui.status-badge :status="$p->status" :label="__(\Illuminate\Support\Str::headline($p->status))" /></td></tr>
        @empty
            <tr><td colspan="6"><x-ui.empty-state :title="__('No pregnancy here.')" icon="bi-person-heart" /></td></tr>
        @endforelse
        </tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
