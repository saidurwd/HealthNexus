@php($p = $procedure ?? null)

<div class="card-body row">
    <div class="col-md-6">
        @unless($p)
        @endunless
        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" value="{{ old('code', $p?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $p?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Modality Type</label>
            <input type="text" name="modality_type" value="{{ old('modality_type', $p?->modality_type) }}" class="form-control @error('modality_type') is-invalid @enderror" placeholder="e.g. CT, MRI, XR, US" required>
            @error('modality_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Section</label>
            <select name="section_id" class="form-control">
                <option value="">-</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ old('section_id', $p?->section_id) == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Body Part</label>
            <select name="body_part_id" class="form-control">
                <option value="">-</option>
                @foreach($bodyParts as $bodyPart)
                    <option value="{{ $bodyPart->id }}" {{ old('body_part_id', $p?->body_part_id) == $bodyPart->id ? 'selected' : '' }}>{{ $bodyPart->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" min="0" name="duration_minutes" value="{{ old('duration_minutes', $p?->duration_minutes) }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Turnaround Time (minutes)</label>
            <input type="number" min="0" name="turnaround_time_minutes" value="{{ old('turnaround_time_minutes', $p?->turnaround_time_minutes) }}" class="form-control">
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="contrast_required" value="1" class="form-check-input" id="contrast_required" {{ old('contrast_required', $p?->contrast_required) ? 'checked' : '' }}>
            <label class="form-check-label" for="contrast_required">Contrast required</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="preparation_required" value="1" class="form-check-input" id="preparation_required" {{ old('preparation_required', $p?->preparation_required) ? 'checked' : '' }}>
            <label class="form-check-label" for="preparation_required">Preparation required</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="sedation_required" value="1" class="form-check-input" id="sedation_required" {{ old('sedation_required', $p?->sedation_required) ? 'checked' : '' }}>
            <label class="form-check-label" for="sedation_required">Sedation required</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="requires_senior_approval" value="1" class="form-check-input" id="requires_senior_approval" {{ old('requires_senior_approval', $p?->requires_senior_approval) ? 'checked' : '' }}>
            <label class="form-check-label" for="requires_senior_approval">Requires senior radiologist approval</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $p?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Preparation Instructions</label>
            <textarea name="preparation_instructions" class="form-control">{{ old('preparation_instructions', $p?->preparation_instructions) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $p?->description) }}</textarea>
        </div>
    </div>
</div>
