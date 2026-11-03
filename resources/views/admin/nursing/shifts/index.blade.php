@extends('layouts.adminlte')

@section('page_title', 'Nursing Shifts')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Shifts') }}</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Start') }}</th><th>{{ __('End') }}</th><th>{{ __('Grace (min)') }}</th><th>{{ __('Active') }}</th></tr></thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $shift->name }}</td><td>{{ $shift->start_time }}</td><td>{{ $shift->end_time }}</td>
                            <td>{{ $shift->grace_period_minutes }}</td><td>{{ $shift->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">{{ __('No shifts configured.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('nursing.shift.manage')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Add Shift') }}</h3></div>
        <form action="{{ route('admin.nursing.shifts.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-3"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required></div>
                <div class="col-md-3"><input type="time" name="start_time" class="form-control" required></div>
                <div class="col-md-3"><input type="time" name="end_time" class="form-control" required></div>
                <div class="col-md-3"><input type="number" name="grace_period_minutes" class="form-control" placeholder="{{ __('Grace (min)') }}"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div>
        </form>
    </div>
    @endcan
@stop
