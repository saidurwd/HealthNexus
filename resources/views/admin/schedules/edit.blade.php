@extends('layouts.adminlte')

@section('page_title', 'Edit Doctor Schedule')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Edit Schedule</h2>
            <a href="{{ route('admin.schedules.show', $schedule) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>

        <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}">
            @csrf
            @method('put')
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $schedule->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $schedule->description) }}</textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Provider</label>
                            <select name="provider_id" class="form-select">
                                <option value="">Select Provider</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ old('provider_id', $schedule->provider_id) == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Specialty</label>
                            <select name="specialty_id" class="form-select">
                                <option value="">Select Specialty</option>
                                @foreach($specialties as $specialty)
                                    <option value="{{ $specialty->id }}" {{ old('specialty_id', $schedule->specialty_id) == $specialty->id ? 'selected' : '' }}>{{ $specialty->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Room</label>
                            <select name="room_id" class="form-select">
                                <option value="">Select Room</option>
                                @foreach(\App\Models\AppointmentRoom::where('company_id', app(\App\Services\TenantContextResolver::class)->getCompanyId())->get() as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id', $schedule->room_id) == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Day of Week *</label>
                        <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                            @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                                <option value="{{ $day }}" {{ old('day_of_week', $schedule->day_of_week) == $day ? 'selected' : '' }}>{{ ucfirst($day) }}</option>
                            @endforeach
                        </select>
                        @error('day_of_week') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $schedule->start_time->format('H:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $schedule->end_time->format('H:i')) }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $schedule->effective_from?->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to', $schedule->effective_to?->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Slot Duration (min)</label>
                            <input type="number" name="slot_duration_minutes" class="form-control" value="{{ old('slot_duration_minutes', $schedule->slot_duration_minutes) }}" min="5" max="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Buffer (min)</label>
                            <input type="number" name="buffer_minutes" class="form-control" value="{{ old('buffer_minutes', $schedule->buffer_minutes) }}" min="0" max="60">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Capacity per Slot</label>
                            <input type="number" name="default_capacity_per_slot" class="form-control" value="{{ old('default_capacity_per_slot', $schedule->default_capacity_per_slot) }}" min="1" max="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Overbooking Allowance</label>
                            <input type="number" name="overbooking_limit" class="form-control" value="{{ old('overbooking_limit', $schedule->overbooking_limit) }}" min="0" max="20">
                            <div class="form-text">Requires the "override" permission to actually use at booking time.</div>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
@endsection
