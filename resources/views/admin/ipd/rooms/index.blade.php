@extends('layouts.adminlte')

@section('page_title', 'IPD Rooms')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Rooms</h3>
            <div class="card-tools">
                @can('ipd.room.create')
                    <a href="{{ route('admin.ipd.rooms.create') }}" class="btn btn-primary btn-sm">New Room</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="ward_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Wards</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Ward</th><th>Room #</th><th>Type</th><th>Capacity</th><th>Gender</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>{{ $room->ward?->name }}</td>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ ucfirst($room->room_type) }}</td>
                            <td>{{ $room->capacity }}</td>
                            <td>{{ ucfirst($room->gender_policy) }}</td>
                            <td><span class="badge {{ $room->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $room->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                @can('ipd.room.update')
                                    <a href="{{ route('admin.ipd.rooms.edit', $room) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No rooms found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rooms->links() }}</div>
    </div>
@stop
