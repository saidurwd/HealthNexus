@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Guardians')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Patient Guardians</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGuardianModal">
                    <i class="bi bi-plus me-1"></i>Add Guardian
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
                                <th>Name</th>
                                <th>Relationship</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th class="text-center">Primary</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($guardians as $guardian)
                                <tr>
                                    <td>{{ $guardian->name }}</td>
                                    <td>{{ $guardian->relationship ?? '-' }}</td>
                                    <td>{{ $guardian->phone ?? '-' }}</td>
                                    <td>{{ $guardian->email ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($guardian->is_primary)
                                            <span class="badge bg-success">Primary</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No guardians recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addGuardianModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.guardians.add', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Guardian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="relationship" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">National ID</label>
                            <input type="text" name="national_identifier" class="form-control">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="guardian_is_primary">
                            <label class="form-check-label" for="guardian_is_primary">Primary guardian</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Guardian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
