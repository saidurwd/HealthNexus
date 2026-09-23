@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Quarantine')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Quarantine Stock</h3></div>
        <form method="POST" action="{{ route('admin.pharmacy.quarantine.store') }}" class="row g-2 p-3">
            @csrf
            <div class="col-md-2">
                <select name="store_id" class="form-control" required>
                    <option value="">Store</option>
                    @foreach(\App\Models\Pharmacy\PharmacyStore::query()->forTenant(app(\App\Services\TenantContextResolver::class)->getCompanyId())->where('is_active', true)->get() as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="batch_id" class="form-control" required>
                    <option value="">Batch</option>
                    @foreach(\App\Models\Pharmacy\PharmacyBatch::query()->where('company_id', app(\App\Services\TenantContextResolver::class)->getCompanyId())->where('is_quarantined', false)->with('medication')->get() as $batch)
                        <option value="{{ $batch->id }}">{{ $batch->medication?->name }} — {{ $batch->batch_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <input type="number" name="quantity" min="1" class="form-control" placeholder="Qty" required>
            </div>
            <div class="col-md-2">
                <select name="reason_type" class="form-control" required>
                    <option value="damage">Damage</option>
                    <option value="recall">Recall</option>
                    <option value="quality">Quality Concern</option>
                    <option value="temperature_excursion">Temp. Excursion</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="reason" class="form-control" placeholder="Reason" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">Quarantine</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Quarantine Records</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Store</th><th>Medication</th><th>Batch</th><th>Qty</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($quarantineRecords as $record)
                        <tr>
                            <td>{{ $record->store?->name }}</td>
                            <td>{{ $record->medication?->name }}</td>
                            <td>{{ $record->batch?->batch_number }}</td>
                            <td>{{ $record->quantity }}</td>
                            <td>{{ $record->reason }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($record->status) }}</span></td>
                            <td>
                                @if($record->status === 'quarantined')
                                    <form method="POST" action="{{ route('admin.pharmacy.quarantine.release', $record) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-success">Release</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.pharmacy.quarantine.dispose', $record) }}" class="d-inline" onsubmit="return confirm('Dispose this quarantined stock permanently?')">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-dark">Dispose</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No quarantine records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $quarantineRecords->links() }}</div>
    </div>
@stop
