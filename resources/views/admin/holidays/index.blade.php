@extends('layouts.adminlte')

@section('page_title', 'Hospital Holidays')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Hospital Holidays</h2>
            <a href="{{ route('admin.holidays.create') }}" class="btn btn-primary"><i class="bi bi-plus me-1"></i>Add Holiday</a>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Scope</th>
                                <th class="text-center">Recurring Annually</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td>{{ $holiday->date->format('M d, Y') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td>{{ $holiday->branch_id ? 'Branch' : 'Hospital-wide' }}{{ $holiday->department_id ? ' / Dept' : '' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $holiday->is_recurring_annually ? 'bg-info text-dark' : 'bg-secondary' }}">{{ $holiday->is_recurring_annually ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this holiday?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No holidays configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($holidays->hasPages())
                <div class="card-footer">{{ $holidays->links() }}</div>
            @endif
        </div>
    </div>
@endsection
