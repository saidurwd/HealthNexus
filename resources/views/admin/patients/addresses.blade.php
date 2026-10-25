@extends('layouts.adminlte')

@section('page_title', $patient->full_name.' - Addresses')

@section('page_content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ __('Patient Addresses') }}</h2>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="bi bi-plus me-1"></i>{{ __('Add Address') }}
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
                                <th>{{ __('Address') }}</th>
                                <th>{{ __('City') }}</th>
                                <th>{{ __('District') }}</th>
                                <th>{{ __('Postal Code') }}</th>
                                <th class="text-center">{{ __('Primary') }}</th>
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
                                            <span class="badge bg-success">{{ __('Primary') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">{{ __('No addresses recorded.') }}</td>
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
                        <h5 class="modal-title">{{ __('Add Address') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Type *') }}</label>
                            <select name="address_type" class="form-select" required>
                                <option value="permanent">{{ __('Permanent') }}</option>
                                <option value="present">{{ __('Present') }}</option>
                                <option value="work">{{ __('Work') }}</option>
                                <option value="mailing">{{ __('Mailing') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Address Line 1') }}</label>
                            <input type="text" name="line1" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Address Line 2') }}</label>
                            <input type="text" name="line2" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('City') }}</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('District') }}</label>
                                <input type="text" name="district" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Postal Code') }}</label>
                            <input type="text" name="postal_code" class="form-control">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="address_is_primary">
                            <label class="form-check-label" for="address_is_primary">{{ __('Primary address for this type') }}</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Address') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
