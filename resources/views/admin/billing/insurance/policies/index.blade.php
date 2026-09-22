@extends('layouts.adminlte')

@section('page_title', 'Insurance Policies')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Insurance Policies</h3>
            <div class="card-tools">
                @can('billing.insurance.policy.manage')
                    <a href="{{ route('admin.billing.insurance.policies.create') }}" class="btn btn-primary btn-sm">New Policy</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Policy #</th><th>Patient</th><th>Provider</th><th>Coverage Limit</th><th>Copay %</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($policies as $policy)
                        <tr>
                            <td>{{ $policy->policy_number }}</td>
                            <td>{{ $policy->patient?->full_name }}</td>
                            <td>{{ $policy->provider?->name }}</td>
                            <td>{{ number_format($policy->coverage_limit, 2) }}</td>
                            <td>{{ $policy->copay_percentage }}%</td>
                            <td><span class="badge {{ $policy->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($policy->status) }}</span></td>
                            <td>
                                @can('billing.insurance.policy.manage')
                                    <a href="{{ route('admin.billing.insurance.policies.edit', $policy) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No insurance policies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $policies->links() }}</div>
    </div>
@stop
