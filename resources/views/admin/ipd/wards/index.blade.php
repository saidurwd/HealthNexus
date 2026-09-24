@extends('layouts.adminlte')

@section('page_title', 'IPD Wards')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Wards</h3>
            <div class="card-tools">
                @can('create', \App\Models\Ipd\IpdWard::class)
                    <a href="{{ route('admin.ipd.wards.create') }}" class="btn btn-primary btn-sm">New Ward</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name...">
            </form>
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Gender Policy</th><th>Capacity</th><th># Rooms</th><th>Isolation</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($wards as $ward)
                        <tr>
                            <td>{{ $ward->code }}</td>
                            <td>{{ $ward->name }}</td>
                            <td>{{ ucfirst($ward->gender_policy) }}</td>
                            <td>{{ $ward->capacity }}</td>
                            <td>{{ $ward->rooms_count }}</td>
                            <td>{{ $ward->isolation_capable ? 'Yes' : 'No' }}</td>
                            <td><span class="badge {{ $ward->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $ward->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                @can('update', $ward)
                                    <a href="{{ route('admin.ipd.wards.edit', $ward) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No wards found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $wards->links() }}</div>
    </div>
@stop
