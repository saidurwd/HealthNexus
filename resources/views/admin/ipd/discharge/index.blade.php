@extends('layouts.adminlte')

@section('page_title', 'Discharge')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Discharge Requests') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['requested','approved','completed'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Admission #') }}</th><th>{{ __('Type') }}</th><th>{{ __('Planned Date') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($dischargeRequests as $req)
                        <tr>
                            <td>{{ $req->patient?->full_name }}</td>
                            <td>{{ $req->admission?->admission_number }}</td>
                            <td>{{ ucfirst($req->discharge_type) }}</td>
                            <td>{{ $req->planned_date?->format('Y-m-d') ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span></td>
                            <td><a href="{{ route('admin.ipd.discharge.show', $req) }}" class="btn btn-xs btn-info">{{ __('View') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No discharge requests found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $dischargeRequests->links() }}</div>
    </div>
@stop
