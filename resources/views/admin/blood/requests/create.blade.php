@extends('layouts.adminlte')

@section('page_title', __('New blood request'))

@section('page_content')
    @include('admin.blood._flash')
    <form method="POST" action="{{ route('admin.blood.requests.store') }}" class="card" data-enter-next>@csrf
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">{{ __('Patient number') }}</label><input name="patient" class="form-control" value="{{ old('patient') }}" required maxlength="40"></div>
            <div class="col-md-4"><label class="form-label">{{ __('Component') }}</label><select name="component" class="form-select" required>@foreach($components as $key => $c)<option value="{{ $key }}">{{ __($c['label']) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">{{ __('Units') }}</label><input type="number" name="units_requested" class="form-control" value="1" min="1" max="20" required></div>
            <div class="col-md-2"><label class="form-label">{{ __('Priority') }}</label><select name="priority" class="form-select">@foreach($priorities as $p)<option value="{{ $p }}">{{ __(\Illuminate\Support\Str::headline($p)) }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">{{ __('Why is blood needed?') }}</label><input name="indication" class="form-control" maxlength="300" value="{{ old('indication') }}"></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" name="replacement_donor_offered" value="1" class="form-check-input"> {{ __('The family is offering replacement donors') }}</label></div>
        </div>
        <div class="card-footer"><button class="btn btn-danger">{{ __('Send request') }}</button></div></form>
@stop
