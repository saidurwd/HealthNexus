@extends('layouts.adminlte')

@section('page_title', 'Price Lists')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Price Lists') }}</h3>
            <div class="card-tools">
                @can('billing.pricing.manage')
                    <a href="{{ route('admin.billing.price-lists.create') }}" class="btn btn-primary btn-sm">{{ __('New Price List') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Currency') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Effective') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
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
                                <a href="{{ route('admin.billing.price-lists.show', $priceList) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                @can('billing.pricing.manage')
                                    <a href="{{ route('admin.billing.price-lists.edit', $priceList) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No price lists found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $priceLists->links() }}</div>
    </div>
@stop
