@extends('layouts.adminlte')

@section('page_title', 'Rooms')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Rooms</h2>
            <a href="{{ route('admin.appointment-rooms.create') }}" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Add Room</a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Code</th><th>Type</th><th>Department</th><th class="text-center">Active</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td>{{ $room->name }}</td>
                                <td>{{ $room->code ?? '-' }}</td>
                                <td>{{ ucfirst($room->room_type) }}</td>
                                <td>{{ $room->department->name ?? '-' }}</td>
                                <td class="text-center"><span class="badge {{ $room->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $room->is_active ? 'Yes' : 'No' }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('admin.appointment-rooms.edit', $room) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.appointment-rooms.destroy', $room) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this room?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No rooms configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rooms->hasPages())
                <div class="card-footer">{{ $rooms->links() }}</div>
            @endif
        </div>
    </div>
@endsection
