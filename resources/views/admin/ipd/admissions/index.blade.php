@extends('layouts.adminlte')

@section('page_title', 'Admissions')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Admissions</h3>
            <div class="card-tools">
                @can('create', \App\Models\Ipd\IpdAdmission::class)
                    <a href="{{ route('admin.ipd.admissions.create') }}" class="btn btn-primary btn-sm">Direct Admission</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search admission #...">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="current_only" value="1" class="form-check-input" id="current_only" {{ request('current_only') ? 'checked' : '' }}>
                        <label class="form-check-label" for="current_only">Current inpatients only</label>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Admission #</th><th>Patient</th><th>Location</th><th>Attending</th><th>Admitted</th><th>Status</th><th></th></tr></thead>
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
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $admission->status)) }}</span></td>
                            <td><a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No admissions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $admissions->links() }}</div>
    </div>
@stop
