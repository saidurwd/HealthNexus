@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Brands')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Brand Catalog') }}</h3>
            <div class="card-tools">
                @can('pharmacy.brand.create')
                    <a href="{{ route('admin.pharmacy.brands.create') }}" class="btn btn-primary btn-sm">{{ __('New Brand') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by brand name...') }}">
            </form>
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Generic') }}</th><th>{{ __('Manufacturer') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                        <tr>
                            <td>{{ $brand->code }}</td>
                            <td>{{ $brand->name }}</td>
                            <td>{{ $brand->generic?->generic_name }}</td>
                            <td>{{ $brand->manufacturer }}</td>
                            <td>
                                <span class="badge {{ $brand->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @can('pharmacy.brand.update')
                                    <a href="{{ route('admin.pharmacy.brands.edit', $brand) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No brands found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $brands->links() }}</div>
    </div>
@stop
