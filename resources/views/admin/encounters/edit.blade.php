@extends('layouts.adminlte')

@section('page_title', 'Edit Encounter')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Encounter</h3>
        </div>
        <form method="POST" action="{{ route('admin.encounters.update', $encounter) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <p class="form-control-plaintext">{{ $encounter->status }}</p>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $encounter->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.encounters.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Encounter</button>
            </div>
        </form>
    </div>
@stop
