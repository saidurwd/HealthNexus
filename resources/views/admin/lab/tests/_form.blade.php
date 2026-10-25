@php($t = $test ?? null)

<div class="card-body row">
    <div class="col-md-6">
        @unless($t)
        @endunless
        <div class="mb-3">
            <label class="form-label">{{ __('Code') }}</label>
            <input type="text" name="code" value="{{ old('code', $t?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Name') }}</label>
            <input type="text" name="name" value="{{ old('name', $t?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Short Name') }}</label>
            <input type="text" name="short_name" value="{{ old('short_name', $t?->short_name) }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Category') }}</label>
            <select name="category_id" class="form-control">
                <option value="">-</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $t?->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Section') }}</label>
            <select name="section_id" class="form-control">
                <option value="">-</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ old('section_id', $t?->section_id) == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Test Type') }}</label>
            <select name="test_type" class="form-control @error('test_type') is-invalid @enderror" required>
                @foreach(['quantitative','qualitative','semi_quantitative','text','categorical','calculated','microbiology','culture','pathology'] as $type)
                    <option value="{{ $type }}" {{ old('test_type', $t?->test_type ?? 'quantitative') == $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">{{ __('Specimen Type') }}</label>
            <select name="specimen_type_id" class="form-control">
                <option value="">-</option>
                @foreach($specimenTypes as $specimenType)
                    <option value="{{ $specimenType->id }}" {{ old('specimen_type_id', $t?->specimen_type_id) == $specimenType->id ? 'selected' : '' }}>{{ $specimenType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Container Type') }}</label>
            <select name="container_type_id" class="form-control">
                <option value="">-</option>
                @foreach($containerTypes as $containerType)
                    <option value="{{ $containerType->id }}" {{ old('container_type_id', $t?->container_type_id) == $containerType->id ? 'selected' : '' }}>{{ $containerType->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Method') }}</label>
            <input type="text" name="method" value="{{ old('method', $t?->method) }}" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Unit') }}</label>
            <input type="text" name="unit" value="{{ old('unit', $t?->unit) }}" class="form-control" placeholder="{{ __('e.g. g/dL, mmol/L') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Turnaround Time (minutes)') }}</label>
            <input type="number" min="0" name="turnaround_time_minutes" value="{{ old('turnaround_time_minutes', $t?->turnaround_time_minutes) }}" class="form-control">
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="fasting_required" value="1" class="form-check-input" id="fasting_required" {{ old('fasting_required', $t?->fasting_required) ? 'checked' : '' }}>
            <label class="form-check-label" for="fasting_required">{{ __('Fasting required') }}</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="requires_pathologist_approval" value="1" class="form-check-input" id="requires_pathologist_approval" {{ old('requires_pathologist_approval', $t?->requires_pathologist_approval) ? 'checked' : '' }}>
            <label class="form-check-label" for="requires_pathologist_approval">{{ __('Requires pathologist approval') }}</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $t?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Description') }}</label>
            <textarea name="description" class="form-control">{{ old('description', $t?->description) }}</textarea>
        </div>
    </div>
</div>
