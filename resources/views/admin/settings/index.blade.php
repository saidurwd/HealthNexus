@extends('layouts.adminlte')

@section('page_title', 'System Settings')

@section('page_content')
<form action="{{ route('admin.settings.update') }}" method="post">
    @csrf
    @method('PUT')

    <div class="row">
        @foreach($settings as $group => $values)
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ ucfirst($group) }}</h3>
                    </div>
                    <div class="card-body">
                        @foreach($values as $key => $value)
                            @php
                                $name = $group . '.' . $key;
                            @endphp

                            @if(is_bool($value))
                                <div class="mb-3 row">
                                    <label for="{{ $name }}" class="col-sm-4 col-form-label">{{ $key }}</label>
                                    <div class="col-sm-8">
                                        <div class="form-check">
                                            <input type="checkbox" name="settings[{{ $name }}]" id="{{ $name }}" value="1" {{ $value ? 'checked' : '' }} class="form-check-input">
                                        </div>
                                    </div>
                                </div>
                            @elseif(is_array($value))
                                <div class="mb-3 row">
                                    <label for="{{ $name }}" class="col-sm-4 col-form-label">{{ $key }}</label>
                                    <div class="col-sm-8">
                                        <textarea name="settings[{{ $name }}]" id="{{ $name }}" rows="3" class="form-control">{{ json_encode($value, JSON_PRETTY_PRINT) }}</textarea>
                                    </div>
                                </div>
                            @else
                                <div class="mb-3 row">
                                    <label for="{{ $name }}" class="col-sm-4 col-form-label">{{ $key }}</label>
                                    <div class="col-sm-8">
                                        <input type="text" name="settings[{{ $name }}]" id="{{ $name }}" value="{{ old('settings.' . $name, $value) }}" class="form-control">
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card-footer">
        <a href="{{ route('home') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </div>
</form>
@stop
