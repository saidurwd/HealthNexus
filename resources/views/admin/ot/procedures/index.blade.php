@extends('layouts.adminlte')

@section('page_title', 'Procedures')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Procedures') }}</h3>
            <a href="{{ route('admin.ot.procedures.create') }}" class="btn btn-primary float-right">{{ __('New Procedure') }}</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Specialty') }}</th><th>{{ __('Category') }}</th><th>{{ __('Duration') }}</th><th>{{ __('Day Care') }}</th></tr></thead>
                <tbody>
                    @forelse($procedures as $procedure)
                        <tr>
                            <td>{{ $procedure->code }}</td>
                            <td>{{ $procedure->name }}</td>
                            <td>{{ $procedure->specialty?->name ?? '—' }}</td>
                            <td>{{ $procedure->category?->name ?? '—' }}</td>
                            <td>{{ $procedure->default_duration_minutes }} min</td>
                            <td>{{ $procedure->day_care_eligible ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No procedures configured.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Add Procedure Category') }}</h3></div>
        <form action="{{ route('admin.ot.procedure-categories.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-5"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required></div>
                <div class="col-md-5"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required></div>
                <div class="col-md-2"><button class="btn btn-primary">{{ __('Save') }}</button></div>
            </div>
        </form>
    </div>
@stop
