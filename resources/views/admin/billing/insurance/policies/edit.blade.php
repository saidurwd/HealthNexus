@extends('layouts.adminlte')

@section('page_title', 'Edit Insurance Policy')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Insurance Policy</h3></div>
        <form action="{{ route('admin.billing.insurance.policies.update', $policy) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Insurance Provider</label>
                        <select name="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror" required>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ old('insurance_provider_id', $policy->insurance_provider_id) == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                            @endforeach
                        </select>
                        @error('insurance_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Policy Number</label>
                        <input type="text" name="policy_number" value="{{ old('policy_number', $policy->policy_number) }}" class="form-control @error('policy_number') is-invalid @enderror" required>
                        @error('policy_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member Number</label>
                        <input type="text" name="member_number" value="{{ old('member_number', $policy->member_number) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Group Number</label>
                        <input type="text" name="group_number" value="{{ old('group_number', $policy->group_number) }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Authorization Reference</label>
                        <input type="text" name="authorization_reference" value="{{ old('authorization_reference', $policy->authorization_reference) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coverage Limit</label>
                        <input type="number" step="0.01" min="0" name="coverage_limit" value="{{ old('coverage_limit', $policy->coverage_limit) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Copay Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="copay_percentage" value="{{ old('copay_percentage', $policy->copay_percentage) }}" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective From</label>
                            <input type="date" name="effective_from" value="{{ old('effective_from', optional($policy->effective_from)->format('Y-m-d')) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="effective_to" value="{{ old('effective_to', optional($policy->effective_to)->format('Y-m-d')) }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', $policy->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $policy->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="expired" {{ old('status', $policy->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.insurance.policies.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Policy</button>
            </div>
        </form>
    </div>
@stop
