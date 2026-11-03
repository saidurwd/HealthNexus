@extends('layouts.adminlte')

@section('page_title', __('Book a pregnancy'))

@section('page_content')
    @include('admin.mch._flash')
    <form method="POST" action="{{ route('admin.mch.pregnancies.store') }}" class="card">@csrf
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="p-patient">{{ __('Woman') }}</label><x-ui.patient-select name="patient_id" id="p-patient" required /></div>
            <div class="col-md-3"><label class="form-label" for="p-lmp">{{ __('First day of the last period') }}</label><input id="p-lmp" type="date" name="lmp" value="{{ old('lmp') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label" for="p-edd">{{ __('Expected date of delivery') }}</label><input id="p-edd" type="date" name="edd" value="{{ old('edd') }}" class="form-control"><div class="form-text">{{ __('Left empty, it is worked out from the last period (280 days).') }}</div></div>
            <div class="col-md-3"><label class="form-label" for="p-g">{{ __('Pregnancies, this one included') }}</label><input id="p-g" type="number" name="gravida" value="{{ old('gravida', 1) }}" class="form-control" min="1" max="20" required></div>
            <div class="col-md-3"><label class="form-label" for="p-p">{{ __('Births before this one') }}</label><input id="p-p" type="number" name="para" value="{{ old('para', 0) }}" class="form-control" min="0" max="19" required></div>
            <div class="col-12"><div class="fw-semibold mb-1">{{ __('Risk factors') }}</div>
                <div class="row">@foreach($risks as $r)<div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="risk_factors[]" value="{{ $r }}" id="rf-{{ $r }}" @checked(in_array($r, (array) old('risk_factors', [])))><label class="form-check-label" for="rf-{{ $r }}">{{ __(\Illuminate\Support\Str::headline($r)) }}</label></div></div>@endforeach</div></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">{{ __('Book') }}</button> <a class="btn btn-link" href="{{ route('admin.mch.pregnancies') }}">{{ __('Cancel') }}</a></div>
    </form>
@stop
