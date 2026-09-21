@extends('layouts.adminlte')

@section('page_title', 'Edit Patient')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Patient</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-info btn-sm">View Profile</a>
            </div>
        </div>
        <form action="{{ route('admin.patients.update', $patient) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $patient->first_name) }}" class="form-control @error('first_name') is-invalid @enderror" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $patient->middle_name) }}" class="form-control @error('middle_name') is-invalid @enderror">
                            @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $patient->last_name) }}" class="form-control @error('last_name') is-invalid @enderror" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}" class="form-control @error('date_of_birth') is-invalid @enderror">
                            @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="sex" class="form-label">Sex</label>
                            <select name="sex" id="sex" class="form-control @error('sex') is-invalid @enderror">
                                <option value="">Select Sex</option>
                                <option value="M" {{ old('sex', $patient->sex) == 'M' ? 'selected' : '' }}>Male</option>
                                <option value="F" {{ old('sex', $patient->sex) == 'F' ? 'selected' : '' }}>Female</option>
                                <option value="O" {{ old('sex', $patient->sex) == 'O' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('sex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="national_identifier" class="form-label">National ID</label>
                            <input type="text" name="national_identifier" id="national_identifier" value="{{ old('national_identifier', $patient->national_identifier) }}" class="form-control @error('national_identifier') is-invalid @enderror">
                            @error('national_identifier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <select name="blood_group" id="blood_group" class="form-control @error('blood_group') is-invalid @enderror">
                                <option value="">Select Blood Group</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                    <option value="{{ $group }}" {{ old('blood_group', $patient->blood_group) == $group ? 'selected' : '' }}>{{ $group }}</option>
                                @endforeach
                            </select>
                            @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $patient->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $patient->email) }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror">{{ old('address', $patient->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $patient->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $patient->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="deceased" {{ old('status', $patient->status) == 'deceased' ? 'selected' : '' }}>Deceased</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Emergency Contact --}}
                @php
                    $emergencyContact = $patient->emergencyContacts->first();
                @endphp
                <div class="row mt-3">
                    <div class="col-12">
                        <h4>Emergency Contact</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="emergency_contact_name" class="form-label">Name *</label>
                            <input type="text" name="emergency_contact[name]" id="emergency_contact_name" value="{{ old('emergency_contact.name', $emergencyContact?->name) }}" class="form-control @error('emergency_contact.name') is-invalid @enderror" required>
                            @error('emergency_contact.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="emergency_contact_phone" class="form-label">Phone *</label>
                            <input type="text" name="emergency_contact[phone]" id="emergency_contact_phone" value="{{ old('emergency_contact.phone', $emergencyContact?->phone) }}" class="form-control @error('emergency_contact.phone') is-invalid @enderror" required>
                            @error('emergency_contact.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                            <input type="text" name="emergency_contact[relationship]" id="emergency_contact_relationship" value="{{ old('emergency_contact.relationship', $emergencyContact?->relationship) }}" class="form-control @error('emergency_contact.relationship') is-invalid @enderror">
                            @error('emergency_contact.relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Next of Kin --}}
                @php
                    $nextOfKinList = $patient->nextOfKin ?? collect();
                @endphp
                <div class="row">
                    <div class="col-12">
                        <h4>Next of Kin</h4>
                    </div>
                    @foreach(range(0, 1) as $index)
                        @php
                            $kin = $nextOfKinList[$index] ?? null;
                        @endphp
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="next_of_kin_{{ $index }}_name" class="form-label">Name @if($index === 0) * @endif</label>
                                <input type="text" name="next_of_kin[{{ $index }}][name]" id="next_of_kin_{{ $index }}_name" value="{{ old('next_of_kin.'.$index.'.name', $kin?->name) }}" class="form-control @error('next_of_kin.'.$index.'.name') is-invalid @enderror" @if($index === 0) required @endif>
                                @error('next_of_kin.'.$index.'.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="next_of_kin_{{ $index }}_phone" class="form-label">Phone</label>
                                <input type="text" name="next_of_kin[{{ $index }}][phone]" id="next_of_kin_{{ $index }}_phone" value="{{ old('next_of_kin.'.$index.'.phone', $kin?->phone) }}" class="form-control @error('next_of_kin.'.$index.'.phone') is-invalid @enderror">
                                @error('next_of_kin.'.$index.'.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="next_of_kin_{{ $index }}_relationship" class="form-label">Relationship</label>
                                <input type="text" name="next_of_kin[{{ $index }}][relationship]" id="next_of_kin_{{ $index }}_relationship" value="{{ old('next_of_kin.'.$index.'.relationship', $kin?->relationship) }}" class="form-control @error('next_of_kin.'.$index.'.relationship') is-invalid @enderror">
                                @error('next_of_kin.'.$index.'.relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Patient</button>
            </div>
        </form>
    </div>
@stop
