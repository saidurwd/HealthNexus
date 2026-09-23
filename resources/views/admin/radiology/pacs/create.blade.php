@extends('layouts.adminlte')

@section('page_title', 'Register PACS Server')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Register PACS Server</h3></div>
        <form action="{{ route('admin.radiology.pacs.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch (optional)</label>
                        <select name="branch_id" class="form-control">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
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
                        <label class="form-label">Adapter</label>
                        <select name="adapter_type" class="form-control" required>
                            <option value="null" {{ old('adapter_type', 'null') == 'null' ? 'selected' : '' }}>None (not connected)</option>
                            <option value="orthanc" {{ old('adapter_type') == 'orthanc' ? 'selected' : '' }}>Orthanc</option>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Set as default PACS server</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Base URL</label>
                        <input type="url" name="base_url" value="{{ old('base_url') }}" class="form-control" placeholder="https://pacs.example.org">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">AE Title</label>
                        <input type="text" name="ae_title" value="{{ old('ae_title') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Port</label>
                        <input type="number" name="port" value="{{ old('port') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" class="form-control" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.radiology.pacs.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save PACS Server</button>
            </div>
        </form>
    </div>
@stop
