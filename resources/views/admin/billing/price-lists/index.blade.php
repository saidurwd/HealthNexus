@extends('layouts.adminlte')

@section('page_title', 'Price Lists')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Price Lists</h3>
            <div class="card-tools">
                @can('billing.pricing.manage')
                    <a href="{{ route('admin.billing.price-lists.create') }}" class="btn btn-primary btn-sm">New Price List</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Currency</th>
                        <th>Priority</th>
                        <th>Effective</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priceLists as $priceList)
                        <tr>
                            <td>{{ $priceList->name }}</td>
                            <td>{{ $priceList->code }}</td>
                            <td>{{ $priceList->currency }}</td>
                            <td>{{ $priceList->priority }}</td>
                            <td>{{ $priceList->effective_from?->format('Y-m-d') ?? '-' }} &ndash; {{ $priceList->effective_to?->format('Y-m-d') ?? 'ongoing' }}</td>
                            <td>
                                <span class="badge {{ $priceList->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($priceList->status) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('admin.billing.price-lists.show', $priceList) }}" class="btn btn-xs btn-info">View</a>
                                @can('billing.pricing.manage')
                                    <a href="{{ route('admin.billing.price-lists.edit', $priceList) }}" class="btn btn-xs btn-warning">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No price lists found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $priceLists->links() }}</div>
    </div>
@stop
