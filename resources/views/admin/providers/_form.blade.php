@php($provider = $provider ?? null)

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $provider?->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Provider Type *</label>
                <select name="provider_type" class="form-select @error('provider_type') is-invalid @enderror" required>
                    @foreach(['doctor', 'consultant', 'specialist', 'general_physician', 'dentist', 'physiotherapist', 'psychologist', 'dietitian', 'nurse', 'technician', 'other'] as $type)
                        <option value="{{ $type }}" {{ old('provider_type', $provider?->provider_type) == $type ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                @error('provider_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Linked User Account</label>
                <select name="user_id" class="form-select">
                    <option value="">None</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ old('user_id', $provider?->user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Optional — not every provider is a system login.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Provider Code</label>
                <input type="text" name="provider_code" class="form-control" value="{{ old('provider_code', $provider?->provider_code) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">None</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $provider?->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Specialty</label>
                <select name="specialty_id" class="form-select">
                    <option value="">None</option>
                    @foreach($specialties as $specialty)
                        <option value="{{ $specialty->id }}" {{ old('specialty_id', $provider?->specialty_id) == $specialty->id ? 'selected' : '' }}>{{ $specialty->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">License Number</label>
                <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $provider?->license_number) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', $provider?->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $provider?->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>
</div>
