@extends('layouts.adminlte')

@section('page_title', 'Examination Queue')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Examination Queue</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" style="max-width:250px;">
                    <option value="">All Statuses</option>
                    @foreach(['scheduled','checked_in','preparing','ready','in_progress'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Procedure</th><th>Modality</th><th>Scheduled At</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($examinations as $examination)
                        <tr>
                            <td>{{ $examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $examination->orderItem->procedure?->name }}</td>
                            <td>{{ $examination->modality?->name }}</td>
                            <td>{{ $examination->scheduled_at?->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $examination->status)) }}</span></td>
                            <td><a href="{{ route('admin.radiology.examinations.show', $examination) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No examinations in the queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $examinations->links() }}</div>
    </div>
@stop
