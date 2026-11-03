@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Medications')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Medication Catalog') }}</h3>
            <div class="card-tools">
                @can('pharmacy.medication.create')
                    <a href="{{ route('admin.pharmacy.medications.create') }}" class="btn btn-primary btn-sm">{{ __('New Medication') }}</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('Search by name or code...') }}">
            </form>
            <table class="table table-striped">
                <thead>
                    <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Strength') }}</th><th>{{ __('Generic') }}</th><th>{{ __('Brand') }}</th><th>{{ __('Flags') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($medications as $medication)
                        <tr>
                            <td>{{ $medication->code }}</td>
                            <td>{{ $medication->name }}</td>
                            <td>{{ $medication->strength }} {{ $medication->strength_unit }}</td>
                            <td>{{ $medication->generic?->generic_name }}</td>
                            <td>{{ $medication->brand?->name }}</td>
                            <td>
                                @if($medication->is_controlled) <span class="badge bg-danger">{{ __('Controlled') }}</span> @endif
                                @if($medication->is_high_alert) <span class="badge bg-warning">{{ __('High Alert') }}</span> @endif
                            </td>
                            <td>
                                <span class="badge {{ $medication->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $medication->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @can('pharmacy.medication.update')
                                    <a href="{{ route('admin.pharmacy.medications.edit', $medication) }}" class="btn btn-xs btn-warning">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">{{ __('No medications found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $medications->links() }}</div>
    </div>
@stop
