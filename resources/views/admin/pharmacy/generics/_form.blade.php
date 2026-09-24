@php($g = $generic ?? null)

<div class="card-body">
    @unless($g)
    @endunless
    <div class="mb-3">
        <label class="form-label">Code</label>
        <input type="text" name="code" value="{{ old('code', $g?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Generic Name</label>
        <input type="text" name="generic_name" value="{{ old('generic_name', $g?->generic_name) }}" class="form-control @error('generic_name') is-invalid @enderror" required>
        @error('generic_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Chemical Name</label>
        <input type="text" name="chemical_name" value="{{ old('chemical_name', $g?->chemical_name) }}" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Therapeutic Class</label>
        <input type="text" name="therapeutic_class" value="{{ old('therapeutic_class', $g?->therapeutic_class) }}" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Pharmacological Class</label>
        <input type="text" name="pharmacological_class" value="{{ old('pharmacological_class', $g?->pharmacological_class) }}" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control">{{ old('description', $g?->description) }}</textarea>
    </div>
    <div class="form-check mb-2">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $g?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
