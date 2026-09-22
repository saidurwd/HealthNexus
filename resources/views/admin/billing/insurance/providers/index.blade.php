@extends('layouts.adminlte')

@section('page_title', 'Insurance Providers')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Insurance Providers</h3>
            <div class="card-tools">
                @can('insurance.create')
                    <a href="{{ route('admin.billing.insurance.providers.create') }}" class="btn btn-primary btn-sm">New Provider</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Code</th><th>Contact</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($providers as $provider)
                        <tr>
                            <td>{{ $provider->name }}</td>
                            <td>{{ $provider->code }}</td>
                            <td>{{ $provider->contact_name }} — {{ $provider->contact_phone }}</td>
                            <td><span class="badge {{ $provider->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($provider->status) }}</span></td>
                            <td>
                                @can('insurance.update')
                                    <a href="{{ route('admin.billing.insurance.providers.edit', $provider) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No insurance providers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $providers->links() }}</div>
    </div>
@stop
