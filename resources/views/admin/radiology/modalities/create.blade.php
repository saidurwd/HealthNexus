@extends('layouts.adminlte')

@section('page_title', 'Register Modality')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Register Modality</h3></div>
        <form action="{{ route('admin.radiology.modalities.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-control">
                            <option value="">-</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room / Bay</label>
                        <select name="room_id" class="form-control">
                            <option value="">-</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modality Type</label>
                        <input type="text" name="modality_type" value="{{ old('modality_type') }}" class="form-control @error('modality_type') is-invalid @enderror" placeholder="e.g. CT, MRI, XR, US" required>
                        @error('modality_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" value="{{ old('manufacturer') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" value="{{ old('model') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" value="{{ old('location') }}" class="form-control">
                    </div>
                    <hr>
                    <h5>DICOM / PACS</h5>
                    <div class="mb-3">
                        <label class="form-label">AE Title</label>
                        <input type="text" name="ae_title" value="{{ old('ae_title') }}" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="ip_address" value="{{ old('ip_address') }}" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" value="{{ old('port') }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PACS Endpoint</label>
                        <input type="text" name="pacs_endpoint" value="{{ old('pacs_endpoint') }}" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.radiology.modalities.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Modality</button>
            </div>
        </form>
    </div>
@stop
