@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Addresses')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Patient Addresses</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="bi bi-plus me-1"></i>Add Address
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
                                <th>Type</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>District</th>
                                <th>Postal Code</th>
                                <th class="text-center">Primary</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($addresses as $address)
                                <tr>
                                    <td>{{ ucfirst($address->address_type) }}</td>
                                    <td>{{ trim(($address->line1 ?? '').' '.($address->line2 ?? '')) ?: '-' }}</td>
                                    <td>{{ $address->city ?? '-' }}</td>
                                    <td>{{ $address->district ?? '-' }}</td>
                                    <td>{{ $address->postal_code ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($address->is_primary)
                                            <span class="badge bg-success">Primary</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No addresses recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.patients.addresses.add', $patient) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Address</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <select name="address_type" class="form-select" required>
                                <option value="permanent">Permanent</option>
                                <option value="present">Present</option>
                                <option value="work">Work</option>
                                <option value="mailing">Mailing</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="line1" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="line2" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="address_is_primary">
                            <label class="form-check-label" for="address_is_primary">Primary address for this type</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
