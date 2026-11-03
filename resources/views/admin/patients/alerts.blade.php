@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Alerts')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Patient Alerts') }}</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAlertModal">
                    <i class="bi bi-plus me-1"></i>{{ __('Add Alert') }}
                </button>
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Profile') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Severity') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Start') }}</th>
                                <th>{{ __('Expiry') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->alert_type }}</td>
                                    <td><strong>{{ $alert->title }}</strong></td>
                                    <td>{{ $alert->description ? Str::limit($alert->description, 50) : '-' }}</td>
                                    <td>
                                        @if($alert->severity === 'critical')
                                            <span class="badge bg-danger">{{ ucfirst($alert->severity) }}</span>
                                        @elseif($alert->severity === 'warning')
                                            <span class="badge bg-warning text-dark">{{ ucfirst($alert->severity) }}</span>
                                        @else
                                            <span class="badge bg-info">{{ ucfirst($alert->severity) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($alert->isActive())
                                            <span class="badge bg-success">{{ __('Active') }}</span>
                                        @else
                                            <x-ui.status-badge :status="$alert->status" />
                                        @endif
                                    </td>
                                    <td>{{ $alert->start_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $alert->expires_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td><small class="text-muted">{{ $alert->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        @if($alert->status !== 'resolved')
                                            <form action="{{ route('admin.patients.alerts.resolve', [$patient, $alert]) }}" method="POST" class="d-inline">
                                                @csrf @method('PUT')
                                                <button class="btn btn-sm btn-outline-success" title="{{ __('Resolve') }}" onclick="return confirm('Mark this alert as resolved?')">
                                                    <i class="bi bi-check"></i> {{ __('Resolve') }}
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.patients.alerts.delete', [$patient, $alert]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this alert?')">
                                            @csrf @method('delete')
                                            <button class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">{{ __('No alerts recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAlertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.alerts.add', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Add Alert') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Type *') }}</label>
                            <input type="text" name="alert_type" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Title *') }}</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Severity') }}</label>
                            <select name="severity" class="form-select">
                                <option value="info">{{ __('Info') }}</option>
                                <option value="warning" selected>{{ __('Warning') }}</option>
                                <option value="critical">{{ __('Critical') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Start Date') }}</label>
                            <input type="date" name="start_at" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Expiry Date') }}</label>
                            <input type="date" name="expires_at" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Alert') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
