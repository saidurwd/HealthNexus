@extends('layouts.adminlte')

@section('page_title', $corporate->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $corporate->name }} ({{ $corporate->code }})</h3>
            <div class="card-tools">
                @can('billing.corporate.manage')
                    <a href="{{ route('admin.billing.corporates.edit', $corporate) }}" class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>{{ __('Contact') }}</th><td>{{ $corporate->contact_name }} — {{ $corporate->contact_email }} — {{ $corporate->contact_phone }}</td></tr>
                <tr><th>{{ __('Credit Limit') }}</th><td>{{ number_format($corporate->credit_limit, 2) }}</td></tr>
                <tr><th>{{ __('Payment Terms') }}</th><td>{{ $corporate->payment_terms_days }} days</td></tr>
                <tr><th>{{ __('Billing Cycle') }}</th><td>{{ ucfirst(str_replace('_',' ',$corporate->billing_cycle)) }}</td></tr>
                <tr><th>{{ __('Status') }}</th><td>{{ ucfirst($corporate->status) }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Contracts') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Discount') }}</th><th>{{ __('Credit Limit') }}</th><th>{{ __('Effective') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($corporate->contracts as $contract)
                        <tr>
                            <td>{{ $contract->name }}</td>
                            <td>{{ $contract->discount_type !== 'none' ? "{$contract->discount_type}: {$contract->discount_value}" : '-' }}</td>
                            <td>{{ number_format($contract->credit_limit, 2) }}</td>
                            <td>{{ $contract->effective_from?->format('Y-m-d') }} &ndash; {{ $contract->effective_to?->format('Y-m-d') ?? 'ongoing' }}</td>
                            <td>{{ ucfirst($contract->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No contracts yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('billing.corporate.manage')
            <div class="card-footer">
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addContract">{{ __('Add Contract') }}</button>
                <div class="collapse mt-3" id="addContract">
                    <form action="{{ route('admin.billing.corporates.contracts.store', $corporate) }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 mb-2"><input type="text" name="name" class="form-control" placeholder="{{ __('Contract name') }}" required></div>
                            <div class="col-md-2 mb-2">
                                <select name="discount_type" class="form-control">
                                    <option value="none">{{ __('No discount') }}</option>
                                    <option value="percentage">{{ __('Percentage') }}</option>
                                    <option value="fixed">{{ __('Fixed') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2"><input type="number" step="0.01" name="discount_value" value="0" class="form-control" placeholder="{{ __('Discount value') }}"></div>
                            <div class="col-md-2 mb-2"><input type="number" step="0.01" name="credit_limit" value="0" class="form-control" placeholder="{{ __('Credit limit') }}" required></div>
                            <div class="col-md-2 mb-2"><input type="number" name="payment_terms_days" value="30" class="form-control" placeholder="{{ __('Terms (days)') }}" required></div>
                            <div class="col-md-2 mb-2"><input type="date" name="effective_from" class="form-control" required></div>
                            <div class="col-md-2 mb-2"><input type="date" name="effective_to" class="form-control"></div>
                            <div class="col-md-2 mb-2">
                                <select name="status" class="form-control">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">{{ __('Add Contract') }}</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Members') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Member #') }}</th><th>{{ __('Relationship') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($corporate->members as $member)
                        <tr>
                            <td>{{ $member->patient?->full_name }}</td>
                            <td>{{ $member->member_number ?? '-' }}</td>
                            <td>{{ ucfirst($member->relationship) }}</td>
                            <td>{{ $member->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No members yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('billing.corporate.manage')
            <div class="card-footer">
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addMember">{{ __('Add Member') }}</button>
                <div class="collapse mt-3" id="addMember">
                    <form action="{{ route('admin.billing.corporates.members.store', $corporate) }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 mb-2"><input type="number" name="patient_id" class="form-control" placeholder="{{ __('Patient ID') }}" required></div>
                            <div class="col-md-3 mb-2"><input type="text" name="member_number" class="form-control" placeholder="{{ __('Member number') }}"></div>
                            <div class="col-md-3 mb-2">
                                <select name="relationship" class="form-control">
                                    <option value="employee">{{ __('Employee') }}</option>
                                    <option value="dependent">{{ __('Dependent') }}</option>
                                    <option value="spouse">{{ __('Spouse') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="is_active" value="1" checked class="form-check-input" id="member_active">
                                    <label class="form-check-label" for="member_active">{{ __('Active') }}</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">{{ __('Add Member') }}</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@stop
