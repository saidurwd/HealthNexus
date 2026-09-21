@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Allergies')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patient Allergies</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-secondary btn-sm">Back to Profile</a>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAllergyModal">
                    Add Allergy
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Substance</th>
                            <th>Severity</th>
                            <th>Reaction</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allergies as $allergy)
                            <tr>
                                <td>{{ $allergy->substance }}</td>
                                <td>{{ ucfirst($allergy->severity ?? '') }}</td>
                                <td>{{ ucfirst($allergy->reaction ?? '') }}</td>
                                <td>{{ $allergy->notes ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $allergy->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $allergy->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $allergy->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form action="{{ route('admin.patients.allergies.delete', [$patient, $allergy]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No allergies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Allergy Modal --}}
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
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
