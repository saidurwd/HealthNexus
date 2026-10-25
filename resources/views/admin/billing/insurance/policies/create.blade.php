@extends('layouts.adminlte')

@section('page_title', 'New Insurance Policy')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Insurance Policy') }}</h3></div>
        <form action="{{ route('admin.billing.insurance.policies.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Insurance Provider') }}</label>
                        <select name="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select Provider') }}</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ old('insurance_provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                            @endforeach
                        </select>
                        @error('insurance_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Patient ID') }}</label>
                        <input type="number" name="patient_id" value="{{ old('patient_id') }}" class="form-control @error('patient_id') is-invalid @enderror" required>
                        @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Policy Number') }}</label>
                        <input type="text" name="policy_number" value="{{ old('policy_number') }}" class="form-control @error('policy_number') is-invalid @enderror" required>
                        @error('policy_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Member Number') }}</label>
                        <input type="text" name="member_number" value="{{ old('member_number') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Group Number') }}</label>
                        <input type="text" name="group_number" value="{{ old('group_number') }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Authorization Reference') }}</label>
                        <input type="text" name="authorization_reference" value="{{ old('authorization_reference') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Coverage Limit') }}</label>
                        <input type="number" step="0.01" min="0" name="coverage_limit" value="{{ old('coverage_limit', 0) }}" class="form-control @error('coverage_limit') is-invalid @enderror" required>
                        @error('coverage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Copay Percentage') }}</label>
                        <input type="number" step="0.01" min="0" max="100" name="copay_percentage" value="{{ old('copay_percentage', 0) }}" class="form-control @error('copay_percentage') is-invalid @enderror" required>
                        @error('copay_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Effective From') }}</label>
                            <input type="date" name="effective_from" value="{{ old('effective_from') }}" class="form-control @error('effective_from') is-invalid @enderror" required>
                            @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Effective To') }}</label>
                            <input type="date" name="effective_to" value="{{ old('effective_to') }}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.insurance.policies.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Policy') }}</button>
            </div>
        </form>
    </div>
@stop
