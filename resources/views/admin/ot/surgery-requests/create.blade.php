@extends('layouts.adminlte')

@section('page_title', 'New Surgery Request')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Surgery Request') }}</h3></div>
        <form action="{{ route('admin.ot.surgery-requests.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-4 mb-3"><label>{{ __('Patient ID') }}</label><input type="number" name="patient_id" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>{{ __('Encounter ID') }}</label><input type="number" name="encounter_id" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>{{ __('Admission ID (optional)') }}</label><input type="number" name="admission_id" class="form-control"></div>
                <div class="col-md-4 mb-3"><label>{{ __('Procedure ID') }}</label><input type="number" name="procedure_id" class="form-control"></div>
                <div class="col-md-4 mb-3"><label>{{ __('Specialty ID') }}</label><input type="number" name="specialty_id" class="form-control"></div>
                <div class="col-md-4 mb-3">
                    <label>{{ __('Priority') }}</label>
                    <select name="priority" class="form-control">
                        @foreach(['routine','urgent','emergency'] as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label>{{ __('Surgery Type') }}</label>
                    <select name="surgery_type" class="form-control">
                        <option value="elective">{{ __('Elective') }}</option>
                        <option value="emergency">{{ __('Emergency') }}</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label>{{ __('Site') }}</label><input name="site" class="form-control"></div>
                <div class="col-md-4 mb-3">
                    <label>{{ __('Laterality') }}</label>
                    <select name="laterality" class="form-control">
                        <option value="">N/A</option>
                        @foreach(['left','right','bilateral','midline'] as $l)<option value="{{ $l }}">{{ ucfirst($l) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-12 mb-3"><label>{{ __('Indication') }}</label><textarea name="indication" class="form-control"></textarea></div>
                <div class="col-md-12 mb-3"><label>{{ __('Notes') }}</label><textarea name="notes" class="form-control"></textarea></div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ot.surgery-requests.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button class="btn btn-primary">{{ __('Submit Request') }}</button>
            </div>
        </form>
    </div>
@stop
