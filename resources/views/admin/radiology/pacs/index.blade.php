@extends('layouts.adminlte')

@section('page_title', 'PACS Servers')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">PACS Servers</h3>
            <div class="card-tools">
                @can('radiology.pacs.manage')
                    <a href="{{ route('admin.radiology.pacs.create') }}" class="btn btn-primary btn-sm">Register PACS Server</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Adapter</th><th>Base URL</th><th>Default</th><th>Status</th></tr></thead>
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
                        <tr><td colspan="6" class="text-center">No PACS servers configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
