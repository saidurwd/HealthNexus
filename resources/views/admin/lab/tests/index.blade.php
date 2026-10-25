@extends('layouts.adminlte')

@section('page_title', 'Test Catalog')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Test Catalog') }}</h3>
            <div class="card-tools">
                @can('lab.test.create')
                    <a href="{{ route('admin.lab.tests.create') }}" class="btn btn-primary btn-sm">{{ __('New Test') }}</a>
                @endcan
                <a href="{{ route('admin.lab.panels.index') }}" class="btn btn-secondary btn-sm">{{ __('Panels') }}</a>
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
                        <th>{{ __('Section') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Unit') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tests as $test)
                        <tr>
                            <td>{{ $test->code }}</td>
                            <td>{{ $test->name }}</td>
                            <td>{{ $test->section?->name }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $test->test_type)) }}</td>
                            <td>{{ $test->unit }}</td>
                            <td>
                                <span class="badge {{ $test->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $test->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @can('lab.test.update')
                                    <a href="{{ route('admin.lab.tests.edit', $test) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('No lab tests found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $tests->links() }}</div>
    </div>
@stop
