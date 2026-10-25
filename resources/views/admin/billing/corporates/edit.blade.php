@extends('layouts.adminlte')

@section('page_title', 'Edit Corporate')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Edit Corporate Organization') }}</h3></div>
        <form action="{{ route('admin.billing.corporates.update', $corporate) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $corporate->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Code') }}</label>
                        <input type="text" name="code" value="{{ old('code', $corporate->code) }}" class="form-control @error('code') is-invalid @enderror" required>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Contact Name') }}</label>
                        <input type="text" name="contact_name" value="{{ old('contact_name', $corporate->contact_name) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Contact Email') }}</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $corporate->contact_email) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Contact Phone') }}</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $corporate->contact_phone) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Credit Limit') }}</label>
                        <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $corporate->credit_limit) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Payment Terms (days)') }}</label>
                        <input type="number" min="0" name="payment_terms_days" value="{{ old('payment_terms_days', $corporate->payment_terms_days) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Billing Cycle') }}</label>
                        <select name="billing_cycle" class="form-control" required>
                            @foreach(['monthly','weekly','biweekly','on_demand'] as $cycle)
                                <option value="{{ $cycle }}" {{ old('billing_cycle', $corporate->billing_cycle) == $cycle ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$cycle)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-control" required>
                            <option value="active" {{ old('status', $corporate->status) == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                            <option value="inactive" {{ old('status', $corporate->status) == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.corporates.show', $corporate) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Update Corporate') }}</button>
            </div>
        </form>
    </div>
@stop
