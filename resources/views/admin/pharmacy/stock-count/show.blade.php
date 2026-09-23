@extends('layouts.adminlte')

@section('page_title', 'Stock Count '.$stockCount->count_number)

@section('page_content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Stock Count {{ $stockCount->count_number }} <span class="badge bg-info">{{ ucfirst($stockCount->status) }}</span></h3>
        <div>
            @if($stockCount->status === 'in_progress')
                <form method="POST" action="{{ route('admin.pharmacy.stock-count.complete', $stockCount) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Complete Count</button>
                </form>
            @elseif($stockCount->status === 'completed')
                <form method="POST" action="{{ route('admin.pharmacy.stock-count.apply-adjustments', $stockCount) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Apply all variances to stock?')">Apply Adjustments</button>
                </form>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pharmacy.stock-count.record', $stockCount) }}">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title">Items — {{ $stockCount->store?->name }}</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Medication</th><th>Batch</th><th>Expected</th><th>Counted</th><th>Variance</th></tr></thead>
                    <tbody>
                        @foreach($stockCount->items as $item)
                            <tr>
                                <td>{{ $item->medication?->name }}</td>
                                <td>{{ $item->batch?->batch_number }}</td>
                                <td>{{ $item->expected_quantity }}</td>
                                <td>
                                    @if($stockCount->status === 'in_progress')
                                        <input type="hidden" name="counts[{{ $loop->index }}][stock_count_item_id]" value="{{ $item->id }}">
                                        <input type="number" name="counts[{{ $loop->index }}][counted_quantity]" value="{{ $item->counted_quantity }}" min="0" class="form-control form-control-sm">
                                    @else
                                        {{ $item->counted_quantity ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    @if($item->variance !== null)
                                        <span class="badge {{ $item->variance == 0 ? 'bg-success' : 'bg-warning' }}">{{ $item->variance }}</span>
                                        @if($item->is_adjusted)<span class="badge bg-secondary">Adjusted</span>@endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($stockCount->status === 'in_progress')
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save Counts</button>
                </div>
            @endif
        </div>
    </form>
@stop
