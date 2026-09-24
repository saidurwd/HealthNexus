@php($r = $room ?? null)

<div class="card-body">
    @unless($r)
    @endunless
    <div class="mb-3">
        <label class="form-label">Ward</label>
        <select name="ward_id" class="form-control @error('ward_id') is-invalid @enderror" required>
            <option value="">Select Ward</option>
            @foreach($wards as $ward)
                <option value="{{ $ward->id }}" {{ old('ward_id', $r?->ward_id) == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
            @endforeach
        </select>
        @error('ward_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Room Number</label>
        <input type="text" name="room_number" value="{{ old('room_number', $r?->room_number) }}" class="form-control @error('room_number') is-invalid @enderror" required>
        @error('room_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Room Type</label>
        <input type="text" name="room_type" value="{{ old('room_type', $r?->room_type ?? 'general') }}" class="form-control" placeholder="e.g. general, private, deluxe, icu">
    </div>
    <div class="mb-3">
        <label class="form-label">Capacity</label>
        <input type="number" min="1" name="capacity" value="{{ old('capacity', $r?->capacity ?? 1) }}" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Gender Policy</label>
        <select name="gender_policy" class="form-control" required>
            <option value="any" {{ old('gender_policy', $r?->gender_policy) == 'any' ? 'selected' : '' }}>Any</option>
            <option value="male" {{ old('gender_policy', $r?->gender_policy) == 'male' ? 'selected' : '' }}>Male</option>
            <option value="female" {{ old('gender_policy', $r?->gender_policy) == 'female' ? 'selected' : '' }}>Female</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Rate Category</label>
        <input type="text" name="rate_category" value="{{ old('rate_category', $r?->rate_category) }}" class="form-control">
    </div>
    <div class="form-check mb-2">
        <input type="checkbox" name="isolation_capable" value="1" class="form-check-input" id="isolation_capable" {{ old('isolation_capable', $r?->isolation_capable) ? 'checked' : '' }}>
        <label class="form-check-label" for="isolation_capable">Isolation capable</label>
    </div>
    <div class="form-check mb-2">
        <input type="checkbox" name="is_vip" value="1" class="form-check-input" id="is_vip" {{ old('is_vip', $r?->is_vip) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_vip">VIP</label>
    </div>
    <div class="form-check mb-2">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $r?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
