@php($room = $room ?? null)

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $room?->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $room?->code) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Room Type *</label>
                <select name="room_type" class="form-select" required>
                    @foreach(['consultation', 'procedure', 'clinic', 'telemedicine'] as $type)
                        <option value="{{ $type }}" {{ old('room_type', $room?->room_type) == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mb-3 mt-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">None</option>
                @foreach(\App\Models\Department::where('company_id', app(\App\Services\TenantContextResolver::class)->getCompanyId())->get() as $department)
                    <option value="{{ $department->id }}" {{ old('department_id', $room?->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-check mb-0">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $room?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>
</div>
