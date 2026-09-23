@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Recalls')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Initiate Recall</h3></div>
        <form method="POST" action="{{ route('admin.pharmacy.recalls.store') }}" class="row g-2 p-3">
            @csrf
            <div class="col-md-4">
                <select name="batch_id" class="form-control" required>
                    <option value="">Select Batch</option>
                    @foreach(\App\Models\Pharmacy\PharmacyBatch::query()->where('company_id', app(\App\Services\TenantContextResolver::class)->getCompanyId())->with('medication')->get() as $batch)
                        <option value="{{ $batch->id }}">{{ $batch->medication?->name }} — {{ $batch->batch_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="reason" class="form-control" placeholder="Recall reason" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">Initiate Recall</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Recalls</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Recall #</th><th>Medication</th><th>Batch</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($recallRecords as $recall)
                        <tr>
                            <td>{{ $recall->recall_number }}</td>
                            <td>{{ $recall->medication?->name }}</td>
                            <td>{{ $recall->batch?->batch_number }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($recall->status) }}</span></td>
                            <td><a href="{{ route('admin.pharmacy.recalls.show', $recall) }}" class="btn btn-xs btn-info">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No recalls found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $recallRecords->links() }}</div>
    </div>
@stop
