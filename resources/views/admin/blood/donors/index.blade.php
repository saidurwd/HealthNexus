@extends('layouts.adminlte')

@section('page_title', __('Blood donors'))

@section('page_content')
    @include('admin.blood._flash')
    <div class="d-flex gap-2 mb-3">
        <form class="d-flex gap-2" method="GET"><input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Name, phone or donor number') }}"><button class="btn btn-outline-secondary">{{ __('Search') }}</button></form>
        @can('bloodbank.donor.create')<a href="{{ route('admin.blood.donors.create') }}" class="btn btn-danger ms-auto">{{ __('Register donor') }}</a>@endcan
    </div>
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Donor') }}</th><th>{{ __('Name') }}</th><th>{{ __('Group') }}</th><th>{{ __('Status') }}</th><th>{{ __('Donations') }}</th><th>{{ __('Last donation') }}</th></tr></thead>
        <tbody>
        @forelse($donors as $d)
            <tr><td><a href="{{ route('admin.blood.donors.show', $d) }}">{{ $d->donor_number }}</a></td><td>{{ $d->patient->full_name }}</td><td>{{ $d->abo ? $d->abo.' '.__(\Illuminate\Support\Str::headline($d->rh)) : '—' }}</td>
                <td><x-ui.status-badge :status="$d->status" /></td><td>{{ $d->donation_count }}</td><td>{{ $d->last_donation_at?->format('d M Y') ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-muted">{{ __('No donors found.') }}</td></tr>
        @endforelse
        </tbody></table></div><div class="card-footer">{{ $donors->links() }}</div></div>
@stop
