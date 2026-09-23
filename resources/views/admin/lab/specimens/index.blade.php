@extends('layouts.adminlte')

@section('page_title', 'Specimens')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Specimens</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by accession number...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach(['pending','collected','in_transit','received','accepted','rejected','processing','completed'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Accession #</th><th>Patient</th><th>Type</th><th>Status</th><th>Collected At</th><th></th></tr></thead>
                <tbody>
                    @forelse($specimens as $specimen)
                        <tr>
                            <td>{{ $specimen->accession_number }}</td>
                            <td>{{ $specimen->labOrder->patient?->full_name }}</td>
                            <td>{{ $specimen->specimenType?->name }}</td>
                            <td><span class="badge {{ $specimen->status === 'rejected' ? 'bg-danger' : 'bg-info' }}">{{ ucfirst($specimen->status) }}</span></td>
                            <td>{{ $specimen->collected_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.lab.specimens.show', $specimen) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No specimens found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $specimens->links() }}</div>
    </div>
@stop
