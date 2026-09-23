@extends('layouts.adminlte')

@section('page_title', 'Edit Modality')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Modality — {{ $modality->name }}</h3></div>
        <form action="{{ route('admin.radiology.modalities.update', $modality) }}" method="post">
            @csrf
            @method('PUT')
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-control">
                            <option value="">-</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ old('section_id', $modality->section_id) == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room / Bay</label>
                        <select name="room_id" class="form-control">
                            <option value="">-</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ old('room_id', $modality->room_id) == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" value="{{ old('code', $modality->code) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $modality->name) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modality Type</label>
                        <input type="text" name="modality_type" value="{{ old('modality_type', $modality->modality_type) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            @foreach(['online','offline','maintenance','disabled'] as $status)
                                <option value="{{ $status }}" {{ old('status', $modality->status) == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $modality->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" name="manufacturer" value="{{ old('manufacturer', $modality->manufacturer) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" value="{{ old('model', $modality->model) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number', $modality->serial_number) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" value="{{ old('location', $modality->location) }}" class="form-control">
                    </div>
                    <hr>
                    <h5>DICOM / PACS</h5>
                    <div class="mb-3">
                        <label class="form-label">AE Title</label>
                        <input type="text" name="ae_title" value="{{ old('ae_title', $modality->ae_title) }}" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">IP Address</label>
                            <input type="text" name="ip_address" value="{{ old('ip_address', $modality->ip_address) }}" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" value="{{ old('port', $modality->port) }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PACS Endpoint</label>
                        <input type="text" name="pacs_endpoint" value="{{ old('pacs_endpoint', $modality->pacs_endpoint) }}" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.radiology.modalities.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Modality</button>
            </div>
        </form>
    </div>
@stop
