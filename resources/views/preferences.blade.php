@extends('layouts.adminlte')

@section('page_title', __('My display settings'))

@section('page_content')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('preferences.update') }}" class="card" style="max-width: 40rem">@csrf
        <div class="card-body">
            <div class="mb-3"><label class="form-label" for="locale">{{ __('Language') }}</label>
                <select id="locale" name="locale" class="form-select">@foreach($locales as $code => $name)<option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ is_array($name) ? ($name['name'] ?? $code) : $name }}</option>@endforeach</select></div>
            <div class="mb-1 fw-semibold">{{ __('Digits in the Bangla screens') }}</div>
            @foreach(['hospital' => ['Follow the hospital', null], 'bangla' => ['Bangla digits (১২৩)', true], 'latin' => ['Latin digits (123)', false]] as $key => [$label, $val])
                <div class="form-check"><input class="form-check-input" type="radio" name="digits" value="{{ $key }}" id="dg-{{ $key }}" @checked($digits === $val)><label class="form-check-label" for="dg-{{ $key }}">{{ __($label) }}</label></div>
            @endforeach
            <div class="form-text mt-2">{{ __('This changes only how figures and dates are written for you, on the dashboards. It does not change any record.') }}</div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div>
    </form>
@stop
