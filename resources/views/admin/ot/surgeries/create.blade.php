@extends('layouts.adminlte')

@section('page_title', 'New Surgical Case')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Surgical Case</h3></div>
        <form action="{{ route('admin.ot.surgeries.store') }}" method="post">
            @csrf
            <div class="card-body row">
                @if($surgeryRequest)
                    <input type="hidden" name="surgery_request_id" value="{{ $surgeryRequest->id }}">
                    <div class="col-md-12 mb-3">From approved request for <strong>{{ $surgeryRequest->patient?->full_name }}</strong> — {{ $surgeryRequest->procedure?->name }}</div>
                @else
                    <div class="col-md-4 mb-3"><label>Patient ID</label><input type="number" name="patient_id" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Encounter ID</label><input type="number" name="encounter_id" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label>Admission ID (optional)</label><input type="number" name="admission_id" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Primary Procedure ID</label><input type="number" name="primary_procedure_id" class="form-control"></div>
                    <div class="col-md-4 mb-3"><label>Specialty ID</label><input type="number" name="specialty_id" class="form-control"></div>
                    <div class="col-md-4 mb-3">
                        <label>Surgery Type</label>
                        <select name="surgery_type" class="form-control">
                            <option value="elective">Elective</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ot.surgeries.index') }}" class="btn btn-secondary">Cancel</a>
                <button class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
@stop
