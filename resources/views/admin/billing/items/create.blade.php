@extends('layouts.adminlte')

@section('page_title', 'New Billing Item')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('New Billing Item') }}</h3></div>
        <form action="{{ route('admin.billing.items.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Code') }}</label>
                        <input type="text" name="item_code" value="{{ old('item_code') }}" class="form-control @error('item_code') is-invalid @enderror" required>
                        @error('item_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Item Type') }}</label>
                        <select name="item_type" class="form-control @error('item_type') is-invalid @enderror" required>
                            @foreach(['service','product','procedure','consultation','diagnostic','room','other'] as $type)
                                <option value="{{ $type }}" {{ old('item_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Unit') }}</label>
                        <input type="text" name="unit" value="{{ old('unit') }}" class="form-control" placeholder="{{ __('e.g. visit, test, item') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Base Price') }}</label>
                        <input type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price') }}" class="form-control @error('base_price') is-invalid @enderror" required>
                        @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tax Category (optional)') }}</label>
                        <select name="tax_category_id" class="form-control">
                            <option value="">{{ __('None') }}</option>
                            @foreach($taxCategories as $taxCategory)
                                <option value="{{ $taxCategory->id }}" {{ old('tax_category_id') == $taxCategory->id ? 'selected' : '' }}>{{ $taxCategory->name }} ({{ $taxCategory->rate }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_taxable" value="1" class="form-check-input" id="is_taxable" {{ old('is_taxable') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_taxable">{{ __('Taxable') }}</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                    </div>
                    <hr>
                    <h5>{{ __('Clinical Auto-Charge (optional)') }}</h5>
                    <p class="text-muted small">{{ __('If set, this item is automatically charged when the matching clinical event occurs.') }}</p>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_clinically_chargeable" value="1" class="form-check-input" id="is_clinically_chargeable" {{ old('is_clinically_chargeable') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_clinically_chargeable">{{ __('Clinically chargeable') }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Clinical Event Type') }}</label>
                        <select name="clinical_event_type" class="form-control">
                            <option value="">-</option>
                            <option value="encounter" {{ old('clinical_event_type') == 'encounter' ? 'selected' : '' }}>{{ __('Encounter') }}</option>
                            <option value="clinical_order" {{ old('clinical_event_type') == 'clinical_order' ? 'selected' : '' }}>{{ __('Clinical Order') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Clinical Event Key') }}</label>
                        <input type="text" name="clinical_event_key" value="{{ old('clinical_event_key') }}" class="form-control" placeholder="{{ __('e.g. completed, lab, radiology') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.items.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Save Item') }}</button>
            </div>
        </form>
    </div>
@stop
