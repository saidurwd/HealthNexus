@extends('layouts.adminlte')

@section('page_title', 'Admissions')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Admissions') }}</h3>
            <div class="card-tools">
                @can('create', \App\Models\Ipd\IpdAdmission::class)
                    <a href="{{ route('admin.ipd.admissions.create') }}" class="btn btn-primary btn-sm">{{ __('Direct Admission') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search admission #...') }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="current_only" value="1" class="form-check-input" id="current_only" {{ request('current_only') ? 'checked' : '' }}>
                        <label class="form-check-label" for="current_only">{{ __('Current inpatients only') }}</label>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Admission #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Location') }}</th><th>{{ __('Attending') }}</th><th>{{ __('Admitted') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($admissions as $admission)
                        <tr>
                            <td>{{ $admission->admission_number }}</td>
                            <td>{{ $admission->patient?->full_name }}</td>
                            <td>
                                @if($admission->currentAllocation?->bed)
                                    {{ $admission->currentAllocation->bed->room?->ward?->name }} / {{ $admission->currentAllocation->bed->bed_code }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $admission->attendingProvider?->name ?? '—' }}</td>
                            <td>{{ $admission->admitted_at?->format('Y-m-d H:i') }}</td>
                            <td><x-ui.status-badge :status="$admission->status" /></td>
                            <td><a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No admissions found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $admissions->links() }}</div>
    </div>
@stop
