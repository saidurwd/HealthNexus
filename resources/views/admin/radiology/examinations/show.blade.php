@extends('layouts.adminlte')

@section('page_title', 'Examination')

@section('page_content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $examination->orderItem->procedure?->name ?? $examination->orderItem->requested_procedure_name }}</h3>
                    <div class="card-tools"><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $examination->status)) }}</span></div>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Patient</th><td>{{ $examination->orderItem->radiologyOrder->patient?->full_name }}</td></tr>
                        <tr><th>Modality</th><td>{{ $examination->modality?->name }}</td></tr>
                        <tr><th>Technologist</th><td>{{ $examination->technologist?->name }}</td></tr>
                        <tr><th>Scheduled At</th><td>{{ $examination->scheduled_at?->format('Y-m-d H:i') }}</td></tr>
                        <tr><th>Checked In</th><td>{{ $examination->check_in_at?->format('Y-m-d H:i') }}</td></tr>
                        <tr><th>Started</th><td>{{ $examination->started_at?->format('Y-m-d H:i') }}</td></tr>
                        <tr><th>Completed</th><td>{{ $examination->completed_at?->format('Y-m-d H:i') }}</td></tr>
                    </table>
                    @if($examination->studies->isNotEmpty())
                        <h5>Studies</h5>
                        <ul>
                            @foreach($examination->studies as $study)
                                <li><a href="{{ route('admin.radiology.studies.show', $study) }}">{{ $study->study_instance_uid ?? 'Pending reconciliation' }}</a> — {{ ucfirst($study->study_status) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="card-footer">
                    @if($examination->status === 'scheduled')
                        @can('radiology.examination.start')
                            <form method="post" action="{{ route('admin.radiology.examinations.check-in', $examination) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Check In</button>
                            </form>
                        @endcan
                    @endif
                    @if($examination->status === 'checked_in')
                        @can('radiology.examination.start')
                            <form method="post" action="{{ route('admin.radiology.examinations.start-preparation', $examination) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info">Start Preparation</button>
                            </form>
                            <form method="post" action="{{ route('admin.radiology.examinations.start', $examination) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">Start Examination</button>
                            </form>
                        @endcan
                    @endif
                    @if($examination->status === 'preparing')
                        @can('radiology.examination.start')
                            <form method="post" action="{{ route('admin.radiology.examinations.start', $examination) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">Start Examination</button>
                            </form>
                        @endcan
                    @endif
                    @if($examination->status === 'in_progress')
                        @can('radiology.examination.complete')
                            <form method="post" action="{{ route('admin.radiology.examinations.complete', $examination) }}" class="d-inline">
                                @csrf
                                <input type="text" name="technical_notes" class="form-control d-inline-block mb-2" style="max-width:300px;" placeholder="Technical notes (optional)">
                                <button type="submit" class="btn btn-success">Complete Examination</button>
                            </form>
                        @endcan
                    @endif
                    @if(in_array($examination->status, ['completed']))
                        @can('radiology.report.create')
                            <a href="{{ route('admin.radiology.reports.create', $examination) }}" class="btn btn-warning">Create Report</a>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
