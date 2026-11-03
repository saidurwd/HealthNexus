@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Batches')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Batches') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search batch number...') }}">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="near_expiry" value="1" class="form-check-input" id="near_expiry" {{ request('near_expiry') ? 'checked' : '' }}>
                        <label class="form-check-label" for="near_expiry">{{ __('Near-expiry only') }}</label>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Batch #') }}</th><th>{{ __('Medication') }}</th><th>{{ __('Expiry Date') }}</th><th>{{ __('Unit Cost') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->batch_number }}</td>
                            <td>{{ $batch->medication?->name }}</td>
                            <td>{{ $batch->expiry_date?->format('Y-m-d') }}</td>
                            <td>{{ $batch->unit_cost }}</td>
                            <td>
                                @if($batch->is_quarantined)
                                    <span class="badge bg-danger">{{ __('Quarantined') }}</span>
                                @elseif($batch->isExpired())
                                    <span class="badge bg-dark">{{ __('Expired') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No batches found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $batches->links() }}</div>
    </div>
@stop
