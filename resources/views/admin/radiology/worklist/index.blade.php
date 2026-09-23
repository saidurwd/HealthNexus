@extends('layouts.adminlte')

@section('page_title', 'Radiologist Worklist')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Radiologist Worklist</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <select name="modality_id" class="form-control">
                        <option value="">All Modalities</option>
                        @foreach($modalities as $modality)
                            <option value="{{ $modality->id }}" {{ request('modality_id') == $modality->id ? 'selected' : '' }}>{{ $modality->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="priority" class="form-control">
                        <option value="">All Priorities</option>
                        @foreach(['stat','urgent','routine'] as $priority)
                            <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="unassigned" value="1" class="form-check-input" id="unassigned" {{ request('unassigned') ? 'checked' : '' }}>
                        <label class="form-check-label" for="unassigned">Unassigned only</label>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Accession #</th><th>Patient</th><th>Procedure</th><th>Modality</th><th>Assigned To</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($examinations as $examination)
                        <tr>
                            <td>{{ $examination->orderItem->radiologyOrder->accession_number }}</td>
                            <td>{{ $examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $examination->orderItem->procedure?->name }}</td>
                            <td>{{ $examination->modality?->name }}</td>
                            <td>{{ $examination->assignedRadiologist?->name ?? '—' }}</td>
                            <td>
                                @can('radiology.worklist.assign')
                                    <form method="post" action="{{ route('admin.radiology.worklist.assign', $examination) }}" class="d-flex gap-1">
                                        @csrf
                                        <select name="radiologist_id" class="form-control form-control-sm" required>
                                            <option value="">Assign to...</option>
                                            @foreach($radiologists as $radiologist)
                                                <option value="{{ $radiologist->id }}">{{ $radiologist->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-xs btn-primary">Assign</button>
                                    </form>
                                @endcan
                                @can('radiology.report.create')
                                    <a href="{{ route('admin.radiology.reports.create', $examination) }}" class="btn btn-xs btn-warning">Report</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">Worklist is empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $examinations->links() }}</div>
    </div>
@stop
