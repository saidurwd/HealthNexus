@php($b = $brand ?? null)

<div class="card-body">
    @unless($b)
    @endunless
    <div class="mb-3">
        <label class="form-label">Code</label>
        <input type="text" name="code" value="{{ old('code', $b?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $b?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Generic</label>
        <select name="generic_id" class="form-control">
            <option value="">-</option>
            @foreach($generics as $generic)
                <option value="{{ $generic->id }}" {{ old('generic_id', $b?->generic_id) == $generic->id ? 'selected' : '' }}>{{ $generic->generic_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Manufacturer</label>
        <input type="text" name="manufacturer" value="{{ old('manufacturer', $b?->manufacturer) }}" class="form-control">
    </div>
    <div class="form-check mb-2">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $b?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
