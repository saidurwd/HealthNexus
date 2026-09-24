@extends('layouts.adminlte')

@section('page_title', 'Medication Administration')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">MAR</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['scheduled','due','administered','held','refused','omitted','missed','cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Patient</th><th>Medication</th><th>Scheduled</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($mar as $row)
                        <tr>
                            <td>{{ $row->patient?->full_name }}</td>
                            <td>{{ $row->medication?->name ?? '—' }} @if($row->is_prn)<span class="badge bg-warning">PRN</span>@endif</td>
                            <td>{{ $row->scheduled_at }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($row->status) }}</span></td>
                            <td><a href="{{ route('admin.nursing.mar.show', $row) }}" class="btn btn-xs btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No medication administration records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $mar->links() }}</div>
    </div>
@stop
