@extends('layouts.adminlte')

@section('page_title', 'Nursing Discharge Checklist')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Checklist ({{ ucfirst($checklist->status) }})</h3></div>
        <form action="{{ route('admin.nursing.discharge-checklist.update', $checklist) }}" method="post">
            @csrf
            @method('PUT')
            <div class="card-body">
                @foreach(['education_completed' => 'Patient education completed', 'medication_education_completed' => 'Medication education completed', 'devices_removed' => 'Devices / lines removed', 'belongings_confirmed' => 'Belongings confirmed', 'follow_up_instructions_given' => 'Follow-up instructions given'] as $field => $label)
                    <div class="form-check">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" class="form-check-input" name="{{ $field }}" value="1" id="{{ $field }}" {{ $checklist->{$field} ? 'checked' : '' }} {{ $checklist->status === 'completed' ? 'disabled' : '' }}>
                        <label for="{{ $field }}" class="form-check-label">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            @if($checklist->status !== 'completed')
                <div class="card-footer"><button class="btn btn-primary">Save</button></div>
            @endif
        </form>
        @if($checklist->status !== 'completed')
            <div class="card-footer">
                <form action="{{ route('admin.nursing.discharge-checklist.complete', $checklist) }}" method="post">@csrf<button class="btn btn-success">Complete Nursing Clearance</button></form>
            </div>
        @endif
    </div>
@stop
