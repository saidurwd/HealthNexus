@extends('layouts.adminlte')

@section('page_title', $provider->name)

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $provider->name }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.providers.edit', $provider) }}" class="btn btn-outline-warning"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
                <a href="{{ route('admin.providers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">{{ __('Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">{{ __('Type') }}</dt>
                            <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $provider->provider_type)) }}</dd>
                            <dt class="col-sm-5">{{ __('Department') }}</dt>
                            <dd class="col-sm-7">{{ $provider->department->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Specialty') }}</dt>
                            <dd class="col-sm-7">{{ $provider->specialty->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('License') }}</dt>
                            <dd class="col-sm-7">{{ $provider->license_number ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Status') }}</dt>
                            <dd class="col-sm-7"><span class="badge {{ $provider->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($provider->status) }}</span></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">{{ __('Schedules') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>{{ __('Name') }}</th><th>{{ __('Day') }}</th><th>{{ __('Time') }}</th></tr>
                            </thead>
                            <tbody>
                                @forelse($provider->schedules as $schedule)
                                    <tr>
                                        <td><a href="{{ route('admin.schedules.show', $schedule) }}">{{ $schedule->name }}</a></td>
                                        <td>{{ ucfirst($schedule->day_of_week) }}</td>
                                        <td>{{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">{{ __('No schedules yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
