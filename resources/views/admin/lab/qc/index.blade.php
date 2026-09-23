@extends('layouts.adminlte')

@section('page_title', 'Quality Control')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quality Control Runs</h3>
            <div class="card-tools">
                @can('lab.qc.create')
                    <a href="{{ route('admin.lab.qc.create') }}" class="btn btn-primary btn-sm">Record QC Run</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Material</th><th>Test</th><th>Observed</th><th>Expected Range</th><th>Status</th><th>Run At</th></tr></thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->qcMaterial->name }} ({{ $run->qcMaterial->level }})</td>
                            <td>{{ $run->test->name }}</td>
                            <td>{{ $run->observed_value }}</td>
                            <td>{{ $run->expected_low }} - {{ $run->expected_high }}</td>
                            <td>
                                <span class="badge {{ $run->isPass() ? 'bg-success' : ($run->isFail() ? 'bg-danger' : 'bg-secondary') }}">{{ ucfirst($run->status) }}</span>
                            </td>
                            <td>{{ $run->run_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No QC runs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $runs->links() }}</div>
    </div>
@stop
