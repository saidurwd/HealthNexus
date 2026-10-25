@extends('layouts.adminlte')

@section('page_title', 'ICU Discharge / Transfer')

@section('page_content')
    <form method="POST" action="{{ route('admin.icu.discharge.store', $episode) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $episode->patient?->full_name }}</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('Destination') }}</label>
                    <select name="destination" class="form-control" required>
                        <option value="ward">{{ __('Ward') }}</option>
                        <option value="hdu">{{ __('HDU') }}</option>
                        <option value="another_icu">{{ __('Another ICU') }}</option>
                        <option value="another_facility">{{ __('Another Facility') }}</option>
                        <option value="discharge">{{ __('Discharge Home') }}</option>
                        <option value="death">{{ __('Death') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('Diagnoses') }}</label>
                    <textarea name="diagnoses" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('Major Events') }}</label>
                    <textarea name="major_events" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('Current Status') }}</label>
                    <textarea name="current_status" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>{{ __('Follow-up Plan') }}</label>
                    <textarea name="follow_up_plan" class="form-control"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ __('Draft Discharge Summary') }}</button>
            </div>
        </div>
    </form>
@stop
