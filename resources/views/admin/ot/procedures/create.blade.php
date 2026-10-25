@extends('layouts.adminlte')

@section('page_title', 'New Procedure')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Procedure</h3></div>
        <form action="{{ route('admin.ot.procedures.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-4 mb-3"><label>Code</label><input name="code" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>Name</label><input name="name" class="form-control" required></div>
                <div class="col-md-4 mb-3">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">None</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Specialty</label>
                    <select name="specialty_id" class="form-control">
                        <option value="">None</option>
                        @foreach($specialties as $specialty)<option value="{{ $specialty->id }}">{{ $specialty->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label>Default Duration (min)</label><input type="number" name="default_duration_minutes" class="form-control" value="60"></div>
                <div class="col-md-4 mb-3"><label>Anesthesia Category</label><input name="anesthesia_category" class="form-control"></div>
                <div class="col-md-12 mb-3">
                    @foreach(['laterality_applicable' => 'Laterality Applicable', 'site_applicable' => 'Site Applicable', 'day_care_eligible' => 'Day Care Eligible', 'emergency_eligible' => 'Emergency Eligible', 'requires_implant' => 'Requires Implant', 'requires_blood' => 'Requires Blood', 'requires_specimen' => 'Requires Specimen'] as $field => $label)
                        <div class="form-check form-check-inline">
                            <input type="checkbox" name="{{ $field }}" value="1" class="form-check-input" id="{{ $field }}">
                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ot.procedures.index') }}" class="btn btn-secondary">Cancel</a>
                <button class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
@stop
