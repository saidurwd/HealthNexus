@php($m = $medication ?? null)

<div class="card-body row">
    <div class="col-md-6">
        @unless($m)
        @endunless
        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" value="{{ old('code', $m?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $m?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Generic</label>
            <select name="generic_id" class="form-control">
                <option value="">-</option>
                @foreach($generics as $generic)
                    <option value="{{ $generic->id }}" {{ old('generic_id', $m?->generic_id) == $generic->id ? 'selected' : '' }}>{{ $generic->generic_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Brand</label>
            <select name="brand_id" class="form-control">
                <option value="">-</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ old('brand_id', $m?->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Dosage Form</label>
            <select name="dosage_form_id" class="form-control">
                <option value="">-</option>
                @foreach($dosageForms as $form)
                    <option value="{{ $form->id }}" {{ old('dosage_form_id', $m?->dosage_form_id) == $form->id ? 'selected' : '' }}>{{ $form->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Route</label>
            <select name="route_id" class="form-control">
                <option value="">-</option>
                @foreach($routes as $route)
                    <option value="{{ $route->id }}" {{ old('route_id', $m?->route_id) == $route->id ? 'selected' : '' }}>{{ $route->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Strength</label>
            <input type="text" name="strength" value="{{ old('strength', $m?->strength) }}" class="form-control" placeholder="e.g. 500">
        </div>
        <div class="mb-3">
            <label class="form-label">Strength Unit</label>
            <input type="text" name="strength_unit" value="{{ old('strength_unit', $m?->strength_unit) }}" class="form-control" placeholder="e.g. mg">
        </div>
        <div class="mb-3">
            <label class="form-label">Pack Size</label>
            <input type="number" min="1" name="pack_size" value="{{ old('pack_size', $m?->pack_size) }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Dispensing Unit</label>
            <input type="text" name="dispensing_unit" value="{{ old('dispensing_unit', $m?->dispensing_unit) }}" class="form-control" placeholder="e.g. tablet">
        </div>
        <div class="mb-3">
            <label class="form-label">Manufacturer</label>
            <input type="text" name="manufacturer" value="{{ old('manufacturer', $m?->manufacturer) }}" class="form-control">
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_prescription_required" value="1" class="form-check-input" id="is_prescription_required" {{ old('is_prescription_required', $m?->is_prescription_required ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_prescription_required">Prescription required</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_controlled" value="1" class="form-check-input" id="is_controlled" {{ old('is_controlled', $m?->is_controlled) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_controlled">Controlled drug</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_high_alert" value="1" class="form-check-input" id="is_high_alert" {{ old('is_high_alert', $m?->is_high_alert) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_high_alert">High-alert medication</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="temperature_sensitive" value="1" class="form-check-input" id="temperature_sensitive" {{ old('temperature_sensitive', $m?->temperature_sensitive) ? 'checked' : '' }}>
            <label class="form-check-label" for="temperature_sensitive">Temperature sensitive</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $m?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>
