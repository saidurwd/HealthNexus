@extends('layouts.adminlte')

@section('page_title', 'Nursing Settings')

@section('page_content')
<form action="{{ route('admin.nursing.settings.update') }}" method="post">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Nursing Settings</h3></div>
        <div class="card-body">
            @foreach($settings as $key => $value)
                <div class="mb-3 row">
                    <label for="{{ $key }}" class="col-sm-4 col-form-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                    <div class="col-sm-8">
                        @if(is_bool($value))
                            <input type="hidden" name="settings[{{ $key }}]" value="0">
                            <input type="checkbox" name="settings[{{ $key }}]" id="{{ $key }}" value="1" {{ $value ? 'checked' : '' }} class="form-check-input">
                        @elseif(is_array($value))
                            <textarea name="settings[{{ $key }}]" id="{{ $key }}" class="form-control" rows="3">{{ old('settings.'.$key, json_encode($value)) }}</textarea>
                        @else
                            <input type="text" name="settings[{{ $key }}]" id="{{ $key }}" value="{{ old('settings.'.$key, $value) }}" class="form-control">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary">Save Settings</button></div>
    </div>
</form>
@stop
