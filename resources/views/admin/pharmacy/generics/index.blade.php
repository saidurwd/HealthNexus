@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Generics')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Generic Catalog') }}</h3>
            <div class="card-tools">
                @can('pharmacy.generic.create')
                    <a href="{{ route('admin.pharmacy.generics.create') }}" class="btn btn-primary btn-sm">{{ __('New Generic') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by generic name...') }}">
            </form>
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Code') }}</th><th>{{ __('Generic Name') }}</th><th>{{ __('Therapeutic Class') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($generics as $generic)
                        <tr>
                            <td>{{ $generic->code }}</td>
                            <td>{{ $generic->generic_name }}</td>
                            <td>{{ $generic->therapeutic_class }}</td>
                            <td>
                                <span class="badge {{ $generic->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $generic->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @can('pharmacy.generic.update')
                                    <a href="{{ route('admin.pharmacy.generics.edit', $generic) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No generics found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $generics->links() }}</div>
    </div>
@stop
