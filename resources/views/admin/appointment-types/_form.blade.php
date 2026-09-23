@php($type = $type ?? null)

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Code *</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $type?->code) }}" required>
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $type?->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Color</label>
                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $type?->color ?? '#6c757d') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $type?->sort_order ?? 0) }}" min="0">
            </div>
        </div>
        <div class="form-check mt-3">
            <input type="checkbox" name="is_follow_up_type" value="1" class="form-check-input" {{ old('is_follow_up_type', $type?->is_follow_up_type) ? 'checked' : '' }}>
            <label class="form-check-label">This is a follow-up type</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="is_telemedicine_type" value="1" class="form-check-input" {{ old('is_telemedicine_type', $type?->is_telemedicine_type) ? 'checked' : '' }}>
            <label class="form-check-label">This is a telemedicine type</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $type?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label">Active</label>
        </div>
    </div>
</div>
