@extends('layouts.adminlte')

@section('page_title', 'IPD Beds')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Beds</h3>
            <div class="card-tools">
                @can('create', \App\Models\Ipd\IpdBed::class)
                    <a href="{{ route('admin.ipd.beds.create') }}" class="btn btn-primary btn-sm">New Bed</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search bed code...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach(['available','reserved','occupied','cleaning','blocked','maintenance','isolation','out_of_service','pending_transfer','pending_discharge'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Bed Code</th><th>Room</th><th>Ward</th><th>Type</th><th>Gender</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($beds as $bed)
                        <tr>
                            <td>{{ $bed->bed_code }}</td>
                            <td>{{ $bed->room?->room_number }}</td>
                            <td>{{ $bed->room?->ward?->name }}</td>
                            <td>{{ $bed->bedType?->name ?? '—' }}</td>
                            <td>{{ ucfirst($bed->gender_type) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $bed->status)) }}</span></td>
                            <td>
                                @can('update', $bed)
                                    <a href="{{ route('admin.ipd.beds.edit', $bed) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                                @can('block', $bed)
                                    @if($bed->status === 'available')
                                        <button type="button" class="btn btn-xs btn-danger" data-bs-toggle="modal" data-bs-target="#block-{{ $bed->id }}">Block</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                        <div class="modal fade" id="block-{{ $bed->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.ipd.beds.block', $bed) }}">
                                        @csrf
                                        <div class="modal-header"><h5 class="modal-title">Block Bed {{ $bed->bed_code }}</h5></div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label class="form-label">Reason Type</label>
                                                <select name="reason_type" class="form-control" required>
                                                    <option value="maintenance">Maintenance</option>
                                                    <option value="cleaning">Cleaning</option>
                                                    <option value="infection_control">Infection Control</option>
                                                    <option value="renovation">Renovation</option>
                                                    <option value="equipment_issue">Equipment Issue</option>
                                                    <option value="administrative">Administrative</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Reason</label>
                                                <input type="text" name="reason" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">Block</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="7" class="text-center">No beds found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $beds->links() }}</div>
    </div>
@stop
