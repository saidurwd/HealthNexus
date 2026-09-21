@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Allergies')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Patient Allergies</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAllergyModal">
                    <i class="bi bi-plus me-1"></i>Add Allergy
                </button>
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Profile
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Substance</th>
                                <th>Severity</th>
                                <th>Reaction</th>
                                <th>Notes</th>
                                <th class="text-center">Status</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
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
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $allergy->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.patients.allergies.delete', [$patient, $allergy]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this allergy?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No allergies recorded.</td>
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
                        <h5 class="modal-title">Add Allergy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Substance *</label>
                            <input type="text" name="substance" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Severity</label>
                            <select name="severity" class="form-select">
                                <option value="">Select</option>
                                <option value="mild">Mild</option>
                                <option value="moderate">Moderate</option>
                                <option value="severe">Severe</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reaction</label>
                            <select name="reaction" class="form-select">
                                <option value="">Select</option>
                                <option value="rash">Rash</option>
                                <option value="hives">Hives</option>
                                <option value="itching">Itching</option>
                                <option value="swelling">Swelling</option>
                                <option value="anaphylaxis">Anaphylaxis</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                        <div class="mb-0 form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Allergy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
