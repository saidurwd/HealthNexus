@extends('layouts.adminlte')

@section('page_title', 'Create Patient')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Patient</h3>
        </div>
        <form action="{{ route('admin.patients.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_id" class="form-label">Company</label>
                            <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                                <option value="">Select Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }} ({{ $company->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="branch_id" class="form-label">Branch</label>
                            <select name="branch_id" id="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->company->name ?? '' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name') }}" class="form-control @error('middle_name') is-invalid @enderror">
                            @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="preferred_name" class="form-label">Preferred Name</label>
                            <input type="text" name="preferred_name" id="preferred_name" value="{{ old('preferred_name') }}" class="form-control @error('preferred_name') is-invalid @enderror">
                            @error('preferred_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="patient_type_id" class="form-label">Patient Type</label>
                                <select name="patient_type_id" id="patient_type_id" class="form-control @error('patient_type_id') is-invalid @enderror">
                                    <option value="">Select</option>
                                    @foreach($patientTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('patient_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="gender_id" class="form-label">Gender</label>
                                <select name="gender_id" id="gender_id" class="form-control @error('gender_id') is-invalid @enderror">
                                    <option value="">Select</option>
                                    @foreach($genders as $gender)
                                        <option value="{{ $gender->id }}" {{ old('gender_id') == $gender->id ? 'selected' : '' }}>{{ $gender->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="marital_status_id" class="form-label">Marital Status</label>
                                <select name="marital_status_id" id="marital_status_id" class="form-control @error('marital_status_id') is-invalid @enderror">
                                    <option value="">Select</option>
                                    @foreach($maritalStatuses as $status)
                                        <option value="{{ $status->id }}" {{ old('marital_status_id') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" class="form-control @error('date_of_birth') is-invalid @enderror">
                            @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="sex" class="form-label">Sex</label>
                            <select name="sex" id="sex" class="form-control @error('sex') is-invalid @enderror">
                                <option value="">Select Sex</option>
                                <option value="M" {{ old('sex') == 'M' ? 'selected' : '' }}>Male</option>
                                <option value="F" {{ old('sex') == 'F' ? 'selected' : '' }}>Female</option>
                                <option value="O" {{ old('sex') == 'O' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('sex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="check_duplicates" class="form-label">Check for duplicates before saving</label>
                            <button type="button" id="check_duplicates" class="btn btn-outline-warning btn-sm">Check Duplicates</button>
                            <div id="duplicates-result" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <select name="blood_group" id="blood_group" class="form-control @error('blood_group') is-invalid @enderror">
                                <option value="">Select Blood Group</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                    <option value="{{ $group }}" {{ old('blood_group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                                @endforeach
                            </select>
                            @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="national_identifier" class="form-label">National ID</label>
                            <input type="text" name="national_identifier" id="national_identifier" value="{{ old('national_identifier') }}" class="form-control @error('national_identifier') is-invalid @enderror">
                            @error('national_identifier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="deceased" {{ old('status') == 'deceased' ? 'selected' : '' }}>Deceased</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Emergency Contact --}}
                <div class="row mt-3">
                    <div class="col-12">
                        <h4>Emergency Contact</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="emergency_contact_name" class="form-label">Name *</label>
                            <input type="text" name="emergency_contact[name]" id="emergency_contact_name" value="{{ old('emergency_contact.name') }}" class="form-control @error('emergency_contact.name') is-invalid @enderror" required>
                            @error('emergency_contact.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="emergency_contact_phone" class="form-label">Phone *</label>
                            <input type="text" name="emergency_contact[phone]" id="emergency_contact_phone" value="{{ old('emergency_contact.phone') }}" class="form-control @error('emergency_contact.phone') is-invalid @enderror" required>
                            @error('emergency_contact.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                            <input type="text" name="emergency_contact[relationship]" id="emergency_contact_relationship" value="{{ old('emergency_contact.relationship') }}" class="form-control @error('emergency_contact.relationship') is-invalid @enderror">
                            @error('emergency_contact.relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Next of Kin --}}
                <div class="row">
                    <div class="col-12">
                        <h4>Next of Kin</h4>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="next_of_kin_0_name" class="form-label">Name *</label>
                            <input type="text" name="next_of_kin[0][name]" id="next_of_kin_0_name" value="{{ old('next_of_kin.0.name') }}" class="form-control @error('next_of_kin.0.name') is-invalid @enderror" required>
                            @error('next_of_kin.0.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="next_of_kin_0_phone" class="form-label">Phone</label>
                            <input type="text" name="next_of_kin[0][phone]" id="next_of_kin_0_phone" value="{{ old('next_of_kin.0.phone') }}" class="form-control @error('next_of_kin.0.phone') is-invalid @enderror">
                            @error('next_of_kin.0.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="next_of_kin_0_relationship" class="form-label">Relationship</label>
                            <input type="text" name="next_of_kin[0][relationship]" id="next_of_kin_0_relationship" value="{{ old('next_of_kin.0.relationship') }}" class="form-control @error('next_of_kin.0.relationship') is-invalid @enderror">
                            @error('next_of_kin.0.relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.patients.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Patient</button>
            </div>
        </form>
    </div>
@stop

@section('js')
<script>
document.getElementById('check_duplicates').addEventListener('click', function() {
    var firstName = document.getElementById('first_name').value;
    var lastName = document.getElementById('last_name').value;
    var phone = document.getElementById('phone').value;
    var nationalId = document.getElementById('national_identifier')?.value || '';
    var email = document.getElementById('email')?.value || '';

    if (!firstName || !lastName) {
        document.getElementById('duplicates-result').innerHTML =
            '<div class="alert alert-warning">Please enter at least first name and last name to check for duplicates.</div>';
        return;
    }

    fetch('{{ route('admin.patients.detect-duplicates') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            first_name: firstName,
            last_name: lastName,
            phone: phone,
            national_identifier: nationalId,
            email: email,
        })
    })
    .then(response => response.json())
    .then(data => {
        var resultDiv = document.getElementById('duplicates-result');
        if (data.duplicates.length === 0) {
            resultDiv.innerHTML = '<div class="alert alert-success">No potential duplicates found.</div>';
        } else {
            var html = '<div class="alert alert-danger"><strong>Potential duplicates found:</strong><ul>';
            data.duplicates.forEach(function(p) {
                html += '<li>' + p.name + ' (' + p.enterprise_patient_no + ')' + (p.phone ? ' Phone: ' + p.phone : '') + (p.national_identifier ? ' ID: ' + p.national_identifier : '') + '</li>';
            });
            html += '</ul></div>';
            resultDiv.innerHTML = html;
        }
    })
    .catch(err => {
        document.getElementById('duplicates-result').innerHTML =
            '<div class="alert alert-warning">Error checking duplicates. Please try again.</div>';
    });
});
</script>
@stop
