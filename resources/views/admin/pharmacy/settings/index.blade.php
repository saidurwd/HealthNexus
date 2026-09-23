@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Settings')

@section('page_content')
<form action="{{ route('admin.pharmacy.settings.update') }}" method="post">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pharmacy Settings</h3>
        </div>
        <div class="card-body">
            @foreach($settings as $key => $value)
                <div class="mb-3 row">
                    <label for="{{ $key }}" class="col-sm-4 col-form-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                    <div class="col-sm-8">
                        @if(is_bool($value))
                            <div class="form-check">
                                <input type="checkbox" name="settings[{{ $key }}]" id="{{ $key }}" value="1" {{ $value ? 'checked' : '' }} class="form-check-input">
                            </div>
                        @elseif(is_array($value))
                            <input type="text" name="settings[{{ $key }}]" id="{{ $key }}" value="{{ old('settings.'.$key, implode(',', $value)) }}" class="form-control">
                        @else
                            <input type="text" name="settings[{{ $key }}]" id="{{ $key }}" value="{{ old('settings.'.$key, $value) }}" class="form-control">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.pharmacy.dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </div>
</form>
@stop
