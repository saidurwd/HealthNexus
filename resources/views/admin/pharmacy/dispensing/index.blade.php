@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Dispensing')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Dispensing Records') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach(['pending','under_review','approved','partially_dispensed','fully_dispensed','cancelled','rejected','returned'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Dispensing #') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Store') }}</th><th>{{ __('Dispensed At') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($dispensings as $dispensing)
                        <tr>
                            <td>{{ $dispensing->dispensing_number }}</td>
                            <td>{{ $dispensing->patient?->full_name }}</td>
                            <td>{{ $dispensing->store?->name }}</td>
                            <td>{{ $dispensing->dispensed_at?->format('Y-m-d H:i') }}</td>
                            <td><x-ui.status-badge :status="$dispensing->status" /></td>
                            <td><a href="{{ route('admin.pharmacy.dispensing.show', $dispensing) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No dispensing records found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $dispensings->links() }}</div>
    </div>
@stop
