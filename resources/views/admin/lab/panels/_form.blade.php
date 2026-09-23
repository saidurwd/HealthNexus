@php($p = $panel ?? null)

<div class="card-body row">
    <div class="col-md-6">
        @unless($p)
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
            <input type="text" name="code" value="{{ old('code', $p?->code) }}" class="form-control @error('code') is-invalid @enderror" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ old('name', $p?->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description', $p?->description) }}</textarea>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $p?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Tests in this panel</label>
        @php($selected = old('test_ids', $p?->items->pluck('test_id')->all() ?? []))
        <select name="test_ids[]" class="form-control @error('test_ids') is-invalid @enderror" multiple size="12" required>
            @foreach($tests as $test)
                <option value="{{ $test->id }}" {{ in_array($test->id, $selected) ? 'selected' : '' }}>{{ $test->code }} — {{ $test->name }}</option>
            @endforeach
        </select>
        @error('test_ids') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Hold Ctrl/Cmd to select multiple tests.</small>
    </div>
</div>
