@extends('layouts.adminlte')

@section('page_title', 'Appointment Types')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Appointment Types</h2>
            <a href="{{ route('admin.appointment-types.create') }}" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Add Type</a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Code</th><th class="text-center">Follow-up</th><th class="text-center">Telemedicine</th><th class="text-center">Active</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                            <tr>
                                <td>
                                    @if($type->color)<span class="badge" style="background-color: {{ $type->color }}">&nbsp;</span>@endif
                                    {{ $type->name }}
                                </td>
                                <td>{{ $type->code }}</td>
                                <td class="text-center">{{ $type->is_follow_up_type ? 'Yes' : '-' }}</td>
                                <td class="text-center">{{ $type->is_telemedicine_type ? 'Yes' : '-' }}</td>
                                <td class="text-center"><span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $type->is_active ? 'Yes' : 'No' }}</span></td>
                                <td class="text-center">
                                    <a href="{{ route('admin.appointment-types.edit', $type) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.appointment-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this type?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No appointment types configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($types->hasPages())
                <div class="card-footer">{{ $types->links() }}</div>
            @endif
        </div>
    </div>
@endsection
