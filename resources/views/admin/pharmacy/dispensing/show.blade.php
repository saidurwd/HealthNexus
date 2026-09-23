@extends('layouts.adminlte')

@section('page_title', 'Dispensing '.$dispensing->dispensing_number)

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Dispensing {{ $dispensing->dispensing_number }} <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $dispensing->status)) }}</span></h3>
        <div>
            @can('verify', $dispensing)
                @if(!$dispensing->verified_at)
                    <form method="POST" action="{{ route('admin.pharmacy.dispensing.verify', $dispensing) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Verify</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Details</h3></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr><th>Patient</th><td>{{ $dispensing->patient?->full_name }}</td></tr>
                <tr><th>Store</th><td>{{ $dispensing->store?->name }}</td></tr>
                <tr><th>Dispensed At</th><td>{{ $dispensing->dispensed_at?->format('Y-m-d H:i') }}</td></tr>
                <tr><th>Dispensed By</th><td>{{ $dispensing->dispensedBy?->name }}</td></tr>
                <tr><th>Verified</th><td>{{ $dispensing->verified_at ? $dispensing->verified_at->format('Y-m-d H:i').' by '.$dispensing->verifiedBy?->name : 'Not yet verified' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Dispensed Items</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Medication</th><th>Batch</th><th>Qty</th><th>Substitution</th></tr></thead>
                <tbody>
                    @foreach($dispensing->items as $item)
                        <tr>
                            <td>{{ $item->medication?->name }}</td>
                            <td>{{ $item->batch?->batch_number }} (exp. {{ $item->batch?->expiry_date?->format('Y-m-d') }})</td>
                            <td>{{ $item->quantity_dispensed }} {{ $item->unit }}</td>
                            <td>
                                @if($item->substitution_flag)
                                    <span class="badge bg-warning">Substituted</span> {{ $item->substitution_reason }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @can('return', $dispensing)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Request Return</h3></div>
            <form method="POST" action="{{ route('admin.pharmacy.dispensing.return', $dispensing) }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                    @foreach($dispensing->items as $item)
                        <div class="form-check mb-1">
                            <input type="checkbox" class="form-check-input" name="items[{{ $loop->index }}][dispensing_item_id]" value="{{ $item->id }}" id="return-{{ $item->id }}">
                            <label class="form-check-label" for="return-{{ $item->id }}">{{ $item->medication?->name }} ({{ $item->quantity_dispensed }} dispensed)</label>
                            <input type="number" name="items[{{ $loop->index }}][quantity_returned]" min="1" max="{{ $item->quantity_dispensed }}" class="form-control form-control-sm d-inline-block w-auto" placeholder="Qty">
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Request Return</button>
                </div>
            </form>
        </div>
    @endcan
@stop
