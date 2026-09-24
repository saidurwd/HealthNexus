@php($b = $bed ?? null)

<div class="card-body row">
    <div class="col-md-6">
        @unless($b)
        @endunless
        <div class="mb-3">
            <label class="form-label">Room</label>
            <select name="room_id" class="form-control @error('room_id') is-invalid @enderror" required>
                <option value="">Select Room</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ old('room_id', $b?->room_id) == $room->id ? 'selected' : '' }}>{{ $room->ward?->name }} — {{ $room->room_number }}</option>
                @endforeach
            </select>
            @error('room_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Bed Code</label>
            <input type="text" name="bed_code" value="{{ old('bed_code', $b?->bed_code) }}" class="form-control @error('bed_code') is-invalid @enderror" required>
            @error('bed_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Bed Name</label>
            <input type="text" name="bed_name" value="{{ old('bed_name', $b?->bed_name) }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Bed Type</label>
            <select name="bed_type_id" class="form-control">
                <option value="">-</option>
                @foreach($bedTypes as $bedType)
                    <option value="{{ $bedType->id }}" {{ old('bed_type_id', $b?->bed_type_id) == $bedType->id ? 'selected' : '' }}>{{ $bedType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Gender Type</label>
            <select name="gender_type" class="form-control" required>
                <option value="any" {{ old('gender_type', $b?->gender_type) == 'any' ? 'selected' : '' }}>Any</option>
                <option value="male" {{ old('gender_type', $b?->gender_type) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender_type', $b?->gender_type) == 'female' ? 'selected' : '' }}>Female</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check mb-2">
            <input type="checkbox" name="isolation_capable" value="1" class="form-check-input" id="isolation_capable" {{ old('isolation_capable', $b?->isolation_capable) ? 'checked' : '' }}>
            <label class="form-check-label" for="isolation_capable">Isolation capable</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="icu_capable" value="1" class="form-check-input" id="icu_capable" {{ old('icu_capable', $b?->icu_capable) ? 'checked' : '' }}>
            <label class="form-check-label" for="icu_capable">ICU capable</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="ventilator_capable" value="1" class="form-check-input" id="ventilator_capable" {{ old('ventilator_capable', $b?->ventilator_capable) ? 'checked' : '' }}>
            <label class="form-check-label" for="ventilator_capable">Ventilator capable</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="oxygen_available" value="1" class="form-check-input" id="oxygen_available" {{ old('oxygen_available', $b?->oxygen_available) ? 'checked' : '' }}>
            <label class="form-check-label" for="oxygen_available">Oxygen available</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="monitor_available" value="1" class="form-check-input" id="monitor_available" {{ old('monitor_available', $b?->monitor_available) ? 'checked' : '' }}>
            <label class="form-check-label" for="monitor_available">Monitor available</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_vip" value="1" class="form-check-input" id="is_vip" {{ old('is_vip', $b?->is_vip) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_vip">VIP</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_pediatric" value="1" class="form-check-input" id="is_pediatric" {{ old('is_pediatric', $b?->is_pediatric) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_pediatric">Pediatric</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_maternity" value="1" class="form-check-input" id="is_maternity" {{ old('is_maternity', $b?->is_maternity) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_maternity">Maternity</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_bariatric" value="1" class="form-check-input" id="is_bariatric" {{ old('is_bariatric', $b?->is_bariatric) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_bariatric">Bariatric</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_accessible" value="1" class="form-check-input" id="is_accessible" {{ old('is_accessible', $b?->is_accessible) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_accessible">Accessible</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $b?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>
