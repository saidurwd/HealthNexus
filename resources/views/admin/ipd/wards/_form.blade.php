@php($w = $ward ?? null)

<div class="card-body row">
    <div class="col-md-6">
        @unless($w)
            <div class="mb-3">
                <label class="form-label">Company</label>
                <select name="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                    <option value="">Select Company</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
                @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Branch (optional)</label>
                <select name="branch_id" class="form-control">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" value="{{ old('code', $w?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $w?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Building</label>
            <select name="building_id" class="form-control">
                <option value="">-</option>
                @foreach($buildings as $building)
                    <option value="{{ $building->id }}" {{ old('building_id', $w?->building_id) == $building->id ? 'selected' : '' }}>{{ $building->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Floor</label>
            <select name="floor_id" class="form-control">
                <option value="">-</option>
                @foreach($floors as $floor)
                    <option value="{{ $floor->id }}" {{ old('floor_id', $w?->floor_id) == $floor->id ? 'selected' : '' }}>{{ $floor->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Gender Policy</label>
            <select name="gender_policy" class="form-control" required>
                <option value="any" {{ old('gender_policy', $w?->gender_policy) == 'any' ? 'selected' : '' }}>Any</option>
                <option value="male" {{ old('gender_policy', $w?->gender_policy) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender_policy', $w?->gender_policy) == 'female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" min="0" name="capacity" value="{{ old('capacity', $w?->capacity ?? 0) }}" class="form-control">
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="isolation_capable" value="1" class="form-check-input" id="isolation_capable" {{ old('isolation_capable', $w?->isolation_capable) ? 'checked' : '' }}>
            <label class="form-check-label" for="isolation_capable">Isolation capable</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $w?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>
