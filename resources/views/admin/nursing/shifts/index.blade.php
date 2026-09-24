@extends('layouts.adminlte')

@section('page_title', 'Nursing Shifts')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Shifts</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Grace (min)</th><th>Active</th></tr></thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $shift->name }}</td><td>{{ $shift->start_time }}</td><td>{{ $shift->end_time }}</td>
                            <td>{{ $shift->grace_period_minutes }}</td><td>{{ $shift->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No shifts configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('nursing.shift.manage')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Add Shift</h3></div>
        <form action="{{ route('admin.nursing.shifts.store') }}" method="post">
            @csrf
            <input type="hidden" name="company_id" value="{{ app(\App\Services\TenantContextResolver::class)->getCompanyId() }}">
            <div class="card-body row">
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-md-3"><input type="time" name="start_time" class="form-control" required></div>
                <div class="col-md-3"><input type="time" name="end_time" class="form-control" required></div>
                <div class="col-md-3"><input type="number" name="grace_period_minutes" class="form-control" placeholder="Grace (min)"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
    @endcan
@stop
