@extends('adminlte::page')

@section('title', 'Edit Encounter')

@section('content_header')
    <h1>Edit Encounter</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.encounters.update', $encounter) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        @foreach (['active', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" {{ old('status', $encounter->status) == $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control">{{ old('notes', $encounter->notes) }}</textarea>
                    @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Update Encounter</button>
            </form>
        </div>
    </div>
@stop
