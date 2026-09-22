@extends('layouts.adminlte')

@section('page_title', 'New Insurance Policy')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Insurance Policy</h3></div>
        <form action="{{ route('admin.billing.insurance.policies.store') }}" method="post">
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
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Insurance Provider</label>
                        <select name="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror" required>
                            <option value="">Select Provider</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ old('insurance_provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                            @endforeach
                        </select>
                        @error('insurance_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Patient ID</label>
                        <input type="number" name="patient_id" value="{{ old('patient_id') }}" class="form-control @error('patient_id') is-invalid @enderror" required>
                        @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Policy Number</label>
                        <input type="text" name="policy_number" value="{{ old('policy_number') }}" class="form-control @error('policy_number') is-invalid @enderror" required>
                        @error('policy_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member Number</label>
                        <input type="text" name="member_number" value="{{ old('member_number') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Group Number</label>
                        <input type="text" name="group_number" value="{{ old('group_number') }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Authorization Reference</label>
                        <input type="text" name="authorization_reference" value="{{ old('authorization_reference') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coverage Limit</label>
                        <input type="number" step="0.01" min="0" name="coverage_limit" value="{{ old('coverage_limit', 0) }}" class="form-control @error('coverage_limit') is-invalid @enderror" required>
                        @error('coverage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Copay Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="copay_percentage" value="{{ old('copay_percentage', 0) }}" class="form-control @error('copay_percentage') is-invalid @enderror" required>
                        @error('copay_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" name="effective_from" value="{{ old('effective_from') }}" class="form-control @error('effective_from') is-invalid @enderror" required>
                            @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="effective_to" value="{{ old('effective_to') }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.insurance.policies.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Policy</button>
            </div>
        </form>
    </div>
@stop
