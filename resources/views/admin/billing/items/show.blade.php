@extends('layouts.adminlte')

@section('page_title', $item->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $item->name }} ({{ $item->item_code }})</h3>
            <div class="card-tools">
                @can('billing.item.manage')
                    <a href="{{ route('admin.billing.items.edit', $item) }}" class="btn btn-warning btn-sm">{{ __('Edit') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>{{ __('Category') }}</th><td>{{ $item->category?->name }}</td></tr>
                <tr><th>{{ __('Type') }}</th><td>{{ ucfirst($item->item_type) }}</td></tr>
                <tr><th>{{ __('Base Price') }}</th><td>{{ number_format($item->base_price, 2) }}</td></tr>
                <tr><th>{{ __('Taxable') }}</th><td>{{ $item->is_taxable ? 'Yes ('.($item->taxCategory?->name).')' : 'No' }}</td></tr>
                <tr><th>{{ __('Clinically Chargeable') }}</th><td>{{ $item->is_clinically_chargeable ? "Yes ({$item->clinical_event_type} / {$item->clinical_event_key})" : 'No' }}</td></tr>
                <tr><th>{{ __('Status') }}</th><td>{{ $item->is_active ? 'Active' : 'Inactive' }}</td></tr>
                <tr><th>{{ __('Description') }}</th><td>{{ $item->description }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Price List Entries') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Price List') }}</th>
                        <th>{{ __('Unit Price') }}</th>
                        <th>{{ __('Discount') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($item->priceListItems as $pli)
                        <tr>
                            <td><a href="{{ route('admin.billing.price-lists.show', $pli->priceList) }}">{{ $pli->priceList?->name }}</a></td>
                            <td>{{ number_format($pli->unit_price, 2) }}</td>
                            <td>{{ $pli->discount_type !== 'none' ? "{$pli->discount_type}: {$pli->discount_value}" : '-' }}</td>
                            <td>{{ $pli->priority }}</td>
                            <td>{{ $pli->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No price list entries yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
