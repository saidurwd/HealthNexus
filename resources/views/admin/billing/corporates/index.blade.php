@extends('layouts.adminlte')

@section('page_title', 'Corporate Companies')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Corporate Companies</h3>
            <div class="card-tools">
                @can('billing.corporate.manage')
                    <a href="{{ route('admin.billing.corporates.create') }}" class="btn btn-primary btn-sm">New Corporate</a>
                @endcan
                <a href="{{ route('admin.billing.corporates.receivables') }}" class="btn btn-secondary btn-sm">Receivables</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>Name</th><th>Code</th><th>Credit Limit</th><th>Billing Cycle</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($corporates as $corporate)
                        <tr>
                            <td>{{ $corporate->name }}</td>
                            <td>{{ $corporate->code }}</td>
                            <td>{{ number_format($corporate->credit_limit, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',$corporate->billing_cycle)) }}</td>
                            <td><span class="badge {{ $corporate->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($corporate->status) }}</span></td>
                            <td>
                                <a href="{{ route('admin.billing.corporates.show', $corporate) }}" class="btn btn-xs btn-info">View</a>
                                @can('billing.corporate.manage')
                                    <a href="{{ route('admin.billing.corporates.edit', $corporate) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No corporate companies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $corporates->links() }}</div>
    </div>
@stop
