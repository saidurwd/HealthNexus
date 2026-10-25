@extends('layouts.adminlte')

@section('page_title', 'New ICU Admission Request')

@section('page_content')
    <form method="POST" action="{{ route('admin.icu.admission-requests.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label>Patient ID</label>
                    <input type="number" name="patient_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Encounter ID</label>
                    <input type="number" name="encounter_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Admission Reason</label>
                    <textarea name="admission_reason" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <option value="routine">Routine</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" name="is_emergency" value="1" class="form-check-input" id="is_emergency">
                    <label class="form-check-label" for="is_emergency">Emergency admission (bypasses approval, requires a reason)</label>
                </div>
                <div class="form-group">
                    <label>Override Reason (required for emergency admission)</label>
                    <input type="text" name="override_reason" class="form-control">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </div>
    </form>
@stop
