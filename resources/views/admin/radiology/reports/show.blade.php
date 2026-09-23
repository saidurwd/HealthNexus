@extends('layouts.adminlte')

@section('page_title', 'Report — '.$report->report_number)

@section('page_content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $report->report_number }} <small class="text-muted">v{{ $report->version }}</small></h3>
                    <div class="card-tools"><span class="badge {{ $report->status === 'amended' ? 'bg-warning' : ($report->isFinal() ? 'bg-success' : 'bg-secondary') }}">{{ ucfirst($report->status) }}</span></div>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Patient</th><td>{{ $report->examination->orderItem->radiologyOrder->patient?->full_name }}</td></tr>
                        <tr><th>Procedure</th><td>{{ $report->examination->orderItem->procedure?->name }}</td></tr>
                        <tr><th>Modality</th><td>{{ $report->examination->modality?->name }}</td></tr>
                        <tr><th>Radiologist</th><td>{{ $report->radiologist?->name }}</td></tr>
                    </table>

                    @if($report->isMutable())
                        <form method="post" action="{{ route('admin.radiology.reports.update', $report) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">Clinical Indication</label>
                                <textarea name="clinical_indication" class="form-control">{{ $report->clinical_indication }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Technique</label>
                                <textarea name="technique" class="form-control">{{ $report->technique }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Findings</label>
                                <textarea name="findings" rows="6" class="form-control">{{ $report->findings }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Impression</label>
                                <textarea name="impression" rows="3" class="form-control">{{ $report->impression }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Recommendation</label>
                                <textarea name="recommendation" class="form-control">{{ $report->recommendation }}</textarea>
                            </div>
                            @can('radiology.report.update')
                                <button type="submit" class="btn btn-secondary">Save Draft</button>
                            @endcan
                        </form>
                    @else
                        <h5>Findings</h5>
                        <p>{{ $report->findings }}</p>
                        <h5>Impression</h5>
                        <p>{{ $report->impression }}</p>
                        @if($report->recommendation)
                            <h5>Recommendation</h5>
                            <p>{{ $report->recommendation }}</p>
                        @endif
                    @endif
                </div>
                <div class="card-footer">
                    @if($report->status === 'draft')
                        @can('radiology.report.submit')
                            <form method="post" action="{{ route('admin.radiology.reports.submit', $report) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">Submit for Approval</button>
                            </form>
                        @endcan
                    @endif
                    @if(in_array($report->status, ['submitted', 'under_review']))
                        @can('radiology.report.approve')
                            <form method="post" action="{{ route('admin.radiology.reports.approve', $report) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Approve &amp; Finalize</button>
                            </form>
                        @endcan
                        @can('radiology.critical_finding.create')
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#flagCriticalModal">Flag Critical Finding</button>
                        @endcan
                    @endif
                    @if($report->isFinal())
                        @can('radiology.report.amend')
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#amendModal">Amend Report</button>
                        @endcan
                    @endif
                </div>
            </div>

            @if($report->criticalFindings->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Critical Findings</h3></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($report->criticalFindings as $finding)
                                <li class="list-group-item">
                                    {{ $finding->finding_text }}
                                    <span class="badge {{ $finding->isAcknowledged() ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($finding->status) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="amendModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.radiology.reports.amend', $report) }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Amend Report</h5></div>
                    <div class="modal-body">
                        <label class="form-label">Findings</label>
                        <textarea name="findings" class="form-control mb-2">{{ $report->findings }}</textarea>
                        <label class="form-label">Impression</label>
                        <textarea name="impression" class="form-control mb-2">{{ $report->impression }}</textarea>
                        <label class="form-label">Reason for amendment</label>
                        <textarea name="reason" class="form-control" required minlength="10"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Save Amendment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="flagCriticalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.radiology.critical-findings.store', $report) }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Flag Critical Finding</h5></div>
                    <div class="modal-body">
                        <textarea name="finding_text" class="form-control" required placeholder="Describe the critical finding..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Flag &amp; Notify</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
