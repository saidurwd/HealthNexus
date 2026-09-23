@extends('layouts.adminlte')

@section('page_title', 'Analyzers')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Analyzers</h3>
            <div class="card-tools">
                @can('lab.analyzer.configure')
                    <a href="{{ route('admin.lab.analyzers.create') }}" class="btn btn-primary btn-sm">Register Analyzer</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Code</th><th>Name</th><th>Section</th><th>Vendor</th><th>Connection</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($analyzers as $analyzer)
                        <tr>
                            <td>{{ $analyzer->code }}</td>
                            <td>{{ $analyzer->name }}</td>
                            <td>{{ $analyzer->section?->name }}</td>
                            <td>{{ $analyzer->vendor }}</td>
                            <td>{{ strtoupper($analyzer->connection_type) }}</td>
                            <td><span class="badge {{ $analyzer->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $analyzer->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No analyzers registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $analyzers->links() }}</div>
    </div>
@stop
