@extends('layouts.adminlte')

@section('page_title', $schedule->name)

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $schedule->name }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.schedules.edit', $schedule) }}" class="btn btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                </a>
                <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">{{ __('Session Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">{{ __('Doctor') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->doctor->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Provider') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->provider->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Department') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->department->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Specialty') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->specialty->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Room') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->room->name ?? '-' }}</dd>
                            <dt class="col-sm-5">{{ __('Day') }}</dt>
                            <dd class="col-sm-7">{{ ucfirst($schedule->day_of_week) }}</dd>
                            <dt class="col-sm-5">{{ __('Time') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}</dd>
                            <dt class="col-sm-5">{{ __('Effective') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->effective_from?->format('M d, Y') ?? 'Always' }} — {{ $schedule->effective_to?->format('M d, Y') ?? 'Ongoing' }}</dd>
                            <dt class="col-sm-5">{{ __('Slot Duration') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->slot_duration_minutes }} min (+{{ $schedule->buffer_minutes }} buffer)</dd>
                            <dt class="col-sm-5">{{ __('Capacity / Overbooking') }}</dt>
                            <dd class="col-sm-7">{{ $schedule->default_capacity_per_slot }} / +{{ $schedule->overbooking_limit }}</dd>
                            <dt class="col-sm-5">{{ __('Active') }}</dt>
                            <dd class="col-sm-7">
                                <span class="badge {{ $schedule->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $schedule->is_active ? 'Yes' : 'No' }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">{{ __('Generate Slots') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.schedules.generate-slots', $schedule) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('From') }}</label>
                                <input type="date" name="from" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('To') }}</label>
                                <input type="date" name="to" class="form-control" value="{{ now()->addDays(30)->toDateString() }}" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{{ __('Generate') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">{{ __('Generated Slots') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th class="text-center">{{ __('Capacity') }}</th>
                                        <th class="text-center">{{ __('Booked') }}</th>
                                        <th class="text-center">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($schedule->slots as $slot)
                                        <tr>
                                            <td>{{ $slot->slot_datetime->format('M d, Y H:i') }}</td>
                                            <td class="text-center">{{ $slot->max_capacity }}</td>
                                            <td class="text-center">{{ $slot->booked_count }}</td>
                                            <td class="text-center">
                                                <x-ui.status-badge :status="$slot->status" />
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">{{ __('No slots generated yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
