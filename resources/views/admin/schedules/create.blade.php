@extends('layouts.adminlte')

@section('page_title', 'Add Doctor Schedule')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Add Doctor Schedule') }}</h2>
            <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
            </a>
        </div>

        <form method="POST" action="{{ route('admin.schedules.store') }}">
            @csrf
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">{{ __('Schedule Details') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Doctor *') }}</label>
                                <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Doctor') }}</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>{{ $doctor->name }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Schedule Name *') }}</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('e.g., Morning Rounds') }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Optional description..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Day of Week *') }}</label>
                                <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Day') }}</option>
                                    <option value="monday" {{ old('day_of_week') == 'monday' ? 'selected' : '' }}>{{ __('Monday') }}</option>
                                    <option value="tuesday" {{ old('day_of_week') == 'tuesday' ? 'selected' : '' }}>{{ __('Tuesday') }}</option>
                                    <option value="wednesday" {{ old('day_of_week') == 'wednesday' ? 'selected' : '' }}>{{ __('Wednesday') }}</option>
                                    <option value="thursday" {{ old('day_of_week') == 'thursday' ? 'selected' : '' }}>{{ __('Thursday') }}</option>
                                    <option value="friday" {{ old('day_of_week') == 'friday' ? 'selected' : '' }}>{{ __('Friday') }}</option>
                                    <option value="saturday" {{ old('day_of_week') == 'saturday' ? 'selected' : '' }}>{{ __('Saturday') }}</option>
                                    <option value="sunday" {{ old('day_of_week') == 'sunday' ? 'selected' : '' }}>{{ __('Sunday') }}</option>
                                </select>
                                @error('day_of_week') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Start Time *') }}</label>
                                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}" required>
                                    @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('End Time *') }}</label>
                                    <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}" required>
                                    @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Provider') }}</label>
                                    <select name="provider_id" class="form-select">
                                        <option value="">{{ __('Select Provider') }}</option>
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider->id }}" {{ old('provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Specialty') }}</label>
                                    <select name="specialty_id" class="form-select">
                                        <option value="">{{ __('Select Specialty') }}</option>
                                        @foreach($specialties as $specialty)
                                            <option value="{{ $specialty->id }}" {{ old('specialty_id') == $specialty->id ? 'selected' : '' }}>{{ $specialty->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Slot Duration (minutes)') }}</label>
                                    <input type="number" name="slot_duration_minutes" class="form-control" value="{{ old('slot_duration_minutes', 15) }}" min="5" max="120">
                                </div>
                            </div>
                            <div class="mb-0 form-check mt-3">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                                <label class="form-check-label">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">{{ __('Generate Slots Now') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_publish_slots" value="1" class="form-check-input" id="publish_slots">
                                <label class="form-check-label" for="publish_slots">{{ __('Generate slots immediately') }}</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Starting Date') }}</label>
                                <input type="date" name="slot_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">{{ __('Number of Days') }}</label>
                                <input type="number" name="generate_days" class="form-control" value="30" min="1" max="90">
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">{{ __('Actions') }}</h5>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-save me-1"></i>{{ __('Save Schedule') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
