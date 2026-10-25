@extends('layouts.adminlte')

@section('page_title', 'PACS Servers')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('PACS Servers') }}</h3>
            <div class="card-tools">
                @can('radiology.pacs.manage')
                    <a href="{{ route('admin.radiology.pacs.create') }}" class="btn btn-primary btn-sm">{{ __('Register PACS Server') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Adapter') }}</th><th>{{ __('Base URL') }}</th><th>{{ __('Default') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($servers as $server)
                        <tr>
                            <td>{{ $server->code }}</td>
                            <td>{{ $server->name }}</td>
                            <td>{{ strtoupper($server->adapter_type) }}</td>
                            <td>{{ $server->base_url }}</td>
                            <td>{{ $server->is_default ? 'Yes' : 'No' }}</td>
                            <td><span class="badge {{ $server->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $server->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No PACS servers configured.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
