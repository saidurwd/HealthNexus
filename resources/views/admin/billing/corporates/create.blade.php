@extends('layouts.adminlte')

@section('page_title', 'New Corporate')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Corporate Company</h3></div>
        <form action="{{ route('admin.billing.corporates.store') }}" method="post">
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
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Contact Name</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credit Limit</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', 0) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Terms (days)</label>
                        <input type="number" min="0" name="payment_terms_days" value="{{ old('payment_terms_days', 30) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing Cycle</label>
                        <select name="billing_cycle" class="form-control" required>
                            <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="weekly" {{ old('billing_cycle') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="biweekly" {{ old('billing_cycle') == 'biweekly' ? 'selected' : '' }}>Biweekly</option>
                            <option value="on_demand" {{ old('billing_cycle') == 'on_demand' ? 'selected' : '' }}>On Demand</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.corporates.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Corporate</button>
            </div>
        </form>
    </div>
@stop
