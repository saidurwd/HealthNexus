@extends('layouts.adminlte')

@section('page_title', 'Insurance Policies')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Insurance Policies') }}</h3>
            <div class="card-tools">
                @can('billing.insurance.policy.manage')
                    <a href="{{ route('admin.billing.insurance.policies.create') }}" class="btn btn-primary btn-sm">{{ __('New Policy') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Policy #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Provider') }}</th><th>{{ __('Coverage Limit') }}</th><th>{{ __('Copay %') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($policies as $policy)
                        <tr>
                            <td>{{ $policy->policy_number }}</td>
                            <td>{{ $policy->patient?->full_name }}</td>
                            <td>{{ $policy->provider?->name }}</td>
                            <td>{{ number_format($policy->coverage_limit, 2) }}</td>
                            <td>{{ $policy->copay_percentage }}%</td>
                            <td><x-ui.status-badge :status="$policy->status" /></td>
                            <td>
                                @can('billing.insurance.policy.manage')
                                    <a href="{{ route('admin.billing.insurance.policies.edit', $policy) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No insurance policies found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $policies->links() }}</div>
    </div>
@stop
