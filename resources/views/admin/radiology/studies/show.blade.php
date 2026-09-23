@extends('layouts.adminlte')

@section('page_title', 'Study — '.$study->accession_number)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $study->accession_number }}</h3>
            <div class="card-tools"><span class="badge {{ $study->isReconciled() ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($study->study_status) }}</span></div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Patient</th><td>{{ $study->patient?->full_name }}</td></tr>
                <tr><th>Procedure</th><td>{{ $study->examination->orderItem->procedure?->name }}</td></tr>
                <tr><th>Study Instance UID</th><td>{{ $study->study_instance_uid ?? '—' }}</td></tr>
                <tr><th>Study Date</th><td>{{ $study->study_date?->format('Y-m-d') }}</td></tr>
                <tr><th>Modality</th><td>{{ $study->modality }}</td></tr>
                <tr><th>Description</th><td>{{ $study->study_description }}</td></tr>
                <tr><th>Series / Instances</th><td>{{ $study->number_of_series }} / {{ $study->number_of_instances }}</td></tr>
                <tr><th>PACS</th><td>{{ $study->pacsServer?->name }}</td></tr>
                <tr><th>Last Synced</th><td>{{ $study->last_synced_at?->format('Y-m-d H:i') }}</td></tr>
            </table>
        </div>
        <div class="card-footer">
            @can('radiology.study.view')
                <form method="post" action="{{ route('admin.radiology.studies.sync', $study) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Check for Images</button>
                </form>
                @if($study->hasImages())
                    <a href="{{ route('admin.radiology.studies.viewer', $study) }}" target="_blank" class="btn btn-primary">Open in Viewer</a>
                @endif
            @endcan
        </div>
    </div>
@stop
