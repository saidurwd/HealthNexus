@extends('layouts.adminlte')

@section('page_title', 'Billing Categories')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Billing Categories') }}</h3>
            <div class="card-tools">
                @can('billing.category.manage')
                    <a href="{{ route('admin.billing.categories.create') }}" class="btn btn-primary btn-sm">{{ __('New Category') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->code }}</td>
                            <td>
                                <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @can('billing.category.manage')
                                    <a href="{{ route('admin.billing.categories.edit', $category) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.billing.categories.destroy', $category) }}" method="post" class="d-inline" onsubmit="return confirm('Deactivate this category?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-xs btn-danger">{{ __('Deactivate') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No categories found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $categories->links() }}</div>
    </div>
@stop
