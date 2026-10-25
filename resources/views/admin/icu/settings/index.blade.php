@extends('layouts.adminlte')

@section('page_title', 'ICU Settings')

@section('page_content')
    <form method="POST" action="{{ route('admin.icu.settings.update') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group form-check">
                    <input type="checkbox" name="admission_requires_approval" value="1" class="form-check-input" id="admission_requires_approval" {{ $settings['admission_requires_approval'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="admission_requires_approval">ICU admission requires approval</label>
                </div>
                <hr>
                <h5>Configured Clinical Scores</h5>
                <ul>
                    @foreach($settings['scoring_definitions'] as $code => $definition)
                        <li>{{ $definition['label'] ?? $code }} (v{{ $definition['version'] ?? 1 }})</li>
                    @endforeach
                </ul>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </div>
    </form>
@stop
