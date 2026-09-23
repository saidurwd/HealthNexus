@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Batches')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Batches</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search batch number...">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="near_expiry" value="1" class="form-check-input" id="near_expiry" {{ request('near_expiry') ? 'checked' : '' }}>
                        <label class="form-check-label" for="near_expiry">Near-expiry only</label>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Batch #</th><th>Medication</th><th>Expiry Date</th><th>Unit Cost</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->batch_number }}</td>
                            <td>{{ $batch->medication?->name }}</td>
                            <td>{{ $batch->expiry_date?->format('Y-m-d') }}</td>
                            <td>{{ $batch->unit_cost }}</td>
                            <td>
                                @if($batch->is_quarantined)
                                    <span class="badge bg-danger">Quarantined</span>
                                @elseif($batch->isExpired())
                                    <span class="badge bg-dark">Expired</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No batches found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $batches->links() }}</div>
    </div>
@stop
