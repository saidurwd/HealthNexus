@extends('layouts.adminlte')

@section('page_title', 'Add Holiday')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Add Holiday') }}</h2>
            <a href="{{ route('admin.holidays.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}</a>
        </div>

        <form method="POST" action="{{ route('admin.holidays.store') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name *') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date *') }}</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date') }}" required>
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_recurring_annually" value="1" class="form-check-input" id="recurring">
                        <label class="form-check-label" for="recurring">{{ __('Recurs every year on this month/day') }}</label>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">{{ __('Save Holiday') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
