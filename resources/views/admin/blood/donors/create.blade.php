@extends('layouts.adminlte')

@section('page_title', __('Register a donor'))

@section('page_content')
    @include('admin.blood._flash')
    <form method="POST" action="{{ route('admin.blood.donors.store') }}" class="card" data-enter-next>
        @csrf
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">{{ __('First name') }}</label><input name="first_name" class="form-control" value="{{ old('first_name') }}" required maxlength="100"></div>
            <div class="col-md-4"><label class="form-label">{{ __('Last name') }}</label><input name="last_name" class="form-control" value="{{ old('last_name') }}" required maxlength="100"></div>
            <div class="col-md-2"><label class="form-label">{{ __('Sex') }}</label><select name="sex" class="form-select" required><option value="M">{{ __('Male') }}</option><option value="F">{{ __('Female') }}</option><option value="O">{{ __('Other') }}</option></select></div>
            <div class="col-md-3"><label class="form-label">{{ __('Date of birth') }}</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required></div>
            <div class="col-md-3"><label class="form-label">{{ __('Phone') }}</label><input name="phone" class="form-control" value="{{ old('phone') }}" maxlength="30"></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" name="replacement" value="1" class="form-check-input"> {{ __('Gives for a named patient (replacement donor)') }}</label></div>
            <div class="col-12"><label class="form-check"><input type="checkbox" name="consent" value="1" class="form-check-input" required> {{ __('The donor has understood and agreed to donate and to be tested.') }}</label></div>
        </div>
        <div class="card-footer"><button class="btn btn-danger">{{ __('Register') }}</button></div>
    </form>
@stop
