@extends('layouts.adminlte')

@section('page_title', 'Register Analyzer')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Register Analyzer</h3></div>
        <form action="{{ route('admin.lab.analyzers.store') }}" method="post">
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
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-control">
                            <option value="">-</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
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
                        <label class="form-label">Vendor</label>
                        <input type="text" name="vendor" value="{{ old('vendor') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Connection Type</label>
                        <select name="connection_type" class="form-control" required>
                            @foreach(['manual','file','astm','hl7'] as $type)
                                <option value="{{ $type }}" {{ old('connection_type', 'manual') == $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Foundation only — no live protocol is implemented yet; results still require manual entry.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.lab.analyzers.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Analyzer</button>
            </div>
        </form>
    </div>
@stop
