@extends('layouts.adminlte')

@section('page_title', 'Request Discharge')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Discharge — {{ $admission->patient?->full_name }}</h3></div>
        <form method="POST" action="{{ route('admin.ipd.discharge.store', $admission) }}">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Discharge Type</label>
                        <select name="discharge_type" class="form-control" required>
                            <option value="routine">Routine</option>
                            <option value="transfer">Transfer</option>
                            <option value="referral">Referral</option>
                            <option value="lama">LAMA</option>
                            <option value="absconded">Absconded</option>
                            <option value="deceased">Deceased</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Planned Date</label>
                        <input type="date" name="planned_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Disposition</label>
                        <select name="disposition_id" class="form-control">
                            <option value="">-</option>
                            @foreach($dispositions as $disposition)
                                <option value="{{ $disposition->id }}">{{ $disposition->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Discharge Diagnosis</label>
                        <textarea name="discharge_diagnosis" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-control"></textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="follow_up_required" value="1" class="form-check-input" id="follow_up_required">
                        <label class="form-check-label" for="follow_up_required">Follow-up required</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Follow-up Provider ID</label>
                        <input type="number" name="follow_up_provider_id" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Request Discharge</button>
            </div>
        </form>
    </div>
@stop
