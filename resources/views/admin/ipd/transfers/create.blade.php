@extends('layouts.adminlte')

@section('page_title', 'Request Transfer')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transfer — {{ $admission->patient?->full_name }}</h3>
        </div>
        <form method="POST" action="{{ route('admin.ipd.transfers.store', $admission) }}">
            @csrf
            <div class="card-body">
                <p><strong>Current bed:</strong> {{ $admission->currentAllocation?->bed?->bed_code ?? '—' }} ({{ $admission->currentAllocation?->bed?->room?->ward?->name }})</p>
                <div class="mb-3">
                    <label class="form-label">Destination Bed</label>
                    <select name="bed_id" class="form-control @error('bed_id') is-invalid @enderror" required>
                        <option value="">Select Bed</option>
                        @foreach($availableBeds as $bed)
                            <option value="{{ $bed->id }}">{{ $bed->room?->ward?->name }} / {{ $bed->room?->room_number }} / {{ $bed->bed_code }}</option>
                        @endforeach
                    </select>
                    @error('bed_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-control"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.ipd.admissions.show', $admission) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Request Transfer</button>
            </div>
        </form>
    </div>
@stop
