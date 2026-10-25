@extends('layouts.adminlte')

@section('page_title', 'ICU Discharge / Transfer')

@section('page_content')
    <form method="POST" action="{{ route('admin.icu.discharge.store', $episode) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $episode->patient?->full_name }}</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Destination</label>
                    <select name="destination" class="form-control" required>
                        <option value="ward">Ward</option>
                        <option value="hdu">HDU</option>
                        <option value="another_icu">Another ICU</option>
                        <option value="another_facility">Another Facility</option>
                        <option value="discharge">Discharge Home</option>
                        <option value="death">Death</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Diagnoses</label>
                    <textarea name="diagnoses" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Major Events</label>
                    <textarea name="major_events" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Current Status</label>
                    <textarea name="current_status" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Follow-up Plan</label>
                    <textarea name="follow_up_plan" class="form-control"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Draft Discharge Summary</button>
            </div>
        </div>
    </form>
@stop
