@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Consents')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Patient Consents') }}</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#grantConsentModal">
                    <i class="bi bi-plus me-1"></i>{{ __('Record Consent') }}
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
                                <th>{{ __('Version') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th>{{ __('Granted By') }}</th>
                                <th>{{ __('Granted At') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consents as $consent)
                                <tr>
                                    <td>{{ $consent->consent_type }}</td>
                                    <td>v{{ $consent->version }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $consent->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($consent->status) }}</span>
                                    </td>
                                    <td>{{ $consent->grantedBy?->name ?? '-' }}</td>
                                    <td><small class="text-muted">{{ $consent->granted_at?->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        @if($consent->status === 'active')
                                            <form action="{{ route('admin.patients.consents.withdraw', [$patient, $consent]) }}" method="POST" class="d-inline" onsubmit="return confirm('Withdraw this consent?')">
                                                @csrf
                                                @method('put')
                                                <button class="btn btn-sm btn-outline-danger">{{ __('Withdraw') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">{{ __('No consents recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="grantConsentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.consents.grant', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Record Consent') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Consent Type *') }}</label>
                            <input type="text" name="consent_type" class="form-control" placeholder="{{ __('e.g. treatment, data_sharing, photography') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Record Consent') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
