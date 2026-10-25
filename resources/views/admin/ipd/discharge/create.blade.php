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
                        <label class="form-label">{{ __('Discharge Type') }}</label>
                        <select name="discharge_type" class="form-control" required>
                            <option value="routine">{{ __('Routine') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                            <option value="referral">{{ __('Referral') }}</option>
                            <option value="lama">{{ __('LAMA') }}</option>
                            <option value="absconded">{{ __('Absconded') }}</option>
                            <option value="deceased">{{ __('Deceased') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Planned Date') }}</label>
                        <input type="date" name="planned_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Disposition') }}</label>
                        <select name="disposition_id" class="form-control">
                            <option value="">-</option>
                            @foreach($dispositions as $disposition)
                                <option value="{{ $disposition->id }}">{{ $disposition->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <textarea name="reason" class="form-control"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Discharge Diagnosis') }}</label>
                        <textarea name="discharge_diagnosis" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Instructions') }}</label>
                        <textarea name="instructions" class="form-control"></textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="follow_up_required" value="1" class="form-check-input" id="follow_up_required">
                        <label class="form-check-label" for="follow_up_required">{{ __('Follow-up required') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Follow-up Provider ID') }}</label>
                        <input type="number" name="follow_up_provider_id" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Follow-up Date') }}</label>
                        <input type="date" name="follow_up_date" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Request Discharge') }}</button>
            </div>
        </form>
    </div>
@stop
