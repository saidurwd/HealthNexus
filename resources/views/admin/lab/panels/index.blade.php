@extends('layouts.adminlte')

@section('page_title', 'Test Panels')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Test Panels') }}</h3>
            <div class="card-tools">
                @can('lab.test.create')
                    <a href="{{ route('admin.lab.panels.create') }}" class="btn btn-primary btn-sm">{{ __('New Panel') }}</a>
                @endcan
                <a href="{{ route('admin.lab.tests.index') }}" class="btn btn-secondary btn-sm">{{ __('Tests') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('# Tests') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($panels as $panel)
                        <tr>
                            <td>{{ $panel->code }}</td>
                            <td>{{ $panel->name }}</td>
                            <td>{{ $panel->items_count }}</td>
                            <td><span class="badge {{ $panel->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $panel->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                @can('lab.test.update')
                                    <a href="{{ route('admin.lab.panels.edit', $panel) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No panels found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $panels->links() }}</div>
    </div>
@stop
