@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Allergies')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Patient Allergies') }}</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAllergyModal">
                    <i class="bi bi-plus me-1"></i>{{ __('Add Allergy') }}
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
                                <th>{{ __('Substance') }}</th>
                                <th>{{ __('Severity') }}</th>
                                <th>{{ __('Reaction') }}</th>
                                <th>{{ __('Notes') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allergies as $allergy)
                                <tr>
                                    <td>
                                        <strong>{{ $allergy->substance }}</strong>
                                    </td>
                                    <td>{{ ucfirst($allergy->severity ?? '') }}</td>
                                    <td>{{ ucfirst($allergy->reaction ?? '') }}</td>
                                    <td>{{ $allergy->notes ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($allergy->is_active)
                                            <span class="badge bg-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $allergy->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.patients.allergies.delete', [$patient, $allergy]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this allergy?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">{{ __('No allergies recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAllergyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.allergies.add', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Add Allergy') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Substance *') }}</label>
                            <input type="text" name="substance" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Severity') }}</label>
                            <select name="severity" class="form-select">
                                <option value="">{{ __('Select') }}</option>
                                <option value="mild">{{ __('Mild') }}</option>
                                <option value="moderate">{{ __('Moderate') }}</option>
                                <option value="severe">{{ __('Severe') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Reaction') }}</label>
                            <select name="reaction" class="form-select">
                                <option value="">{{ __('Select') }}</option>
                                <option value="rash">{{ __('Rash') }}</option>
                                <option value="hives">{{ __('Hives') }}</option>
                                <option value="itching">{{ __('Itching') }}</option>
                                <option value="swelling">{{ __('Swelling') }}</option>
                                <option value="anaphylaxis">{{ __('Anaphylaxis') }}</option>
                                <option value="other">{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                        <div class="mb-0 form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <label class="form-check-label">{{ __('Active') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Allergy') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
