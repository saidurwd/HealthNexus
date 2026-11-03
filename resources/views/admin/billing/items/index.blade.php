@extends('layouts.adminlte')

@section('page_title', 'Service Catalog')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Service Catalog') }}</h3>
            <div class="card-tools">
                @can('billing.item.manage')
                    <a href="{{ route('admin.billing.items.create') }}" class="btn btn-primary btn-sm">{{ __('New Item') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <div class="input-group">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by name or code...') }}">
                    <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Base Price') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->item_code }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->category?->name }}</td>
                            <td>{{ ucfirst($item->item_type) }}</td>
                            <td>{{ number_format($item->base_price, 2) }}</td>
                            <td>
                                <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.billing.items.show', $item) }}" class="btn btn-xs btn-info">{{ __('View') }}</a>
                                @can('billing.item.manage')
                                    <a href="{{ route('admin.billing.items.edit', $item) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No billing items found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@stop
