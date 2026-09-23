@extends('layouts.adminlte')

@section('page_title', 'Schedule Examination')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Schedule — {{ $item->procedure?->name ?? $item->requested_procedure_name }}</h3></div>
        <form action="{{ route('admin.radiology.scheduling.store', $item) }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Modality</label>
                        <select name="modality_id" class="form-control @error('modality_id') is-invalid @enderror" required>
                            <option value="">Select Modality</option>
                            @foreach($modalities as $modality)
                                <option value="{{ $modality->id }}" {{ old('modality_id') == $modality->id ? 'selected' : '' }}>{{ $modality->name }} ({{ $modality->modality_type }})</option>
                            @endforeach
                        </select>
                        @error('modality_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Technologist / Radiologist</label>
                        <select name="technologist_id" class="form-control @error('technologist_id') is-invalid @enderror" required>
                            <option value="">Select Staff</option>
                            @foreach($technologists as $technologist)
                                <option value="{{ $technologist->id }}" {{ old('technologist_id') == $technologist->id ? 'selected' : '' }}>{{ $technologist->name }}</option>
                            @endforeach
                        </select>
                        @error('technologist_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Must have a linked user account to be scheduled.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Date/Time</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-control @error('scheduled_at') is-invalid @enderror" required>
                        @error('scheduled_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" min="5" max="240" name="duration_minutes" value="{{ old('duration_minutes', $item->procedure?->duration_minutes ?? 30) }}" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.radiology.orders.show', $item->radiologyOrder) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Schedule Examination</button>
            </div>
        </form>
    </div>
@stop
