@extends('layouts.adminlte')

@section('page_title', 'Edit Billing Item')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Billing Item</h3></div>
        <form action="{{ route('admin.billing.items.update', $item) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Code</label>
                        <input type="text" name="item_code" value="{{ old('item_code', $item->item_code) }}" class="form-control @error('item_code') is-invalid @enderror" required>
                        @error('item_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Type</label>
                        <select name="item_type" class="form-control" required>
                            @foreach(['service','product','procedure','consultation','diagnostic','room','other'] as $type)
                                <option value="{{ $type }}" {{ old('item_type', $item->item_type) == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $item->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" value="{{ old('unit', $item->unit) }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Base Price</label>
                        <input type="number" step="0.01" min="0" name="base_price" value="{{ old('base_price', $item->base_price) }}" class="form-control @error('base_price') is-invalid @enderror" required>
                        @error('base_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Category</label>
                        <select name="tax_category_id" class="form-control">
                            <option value="">None</option>
                            @foreach($taxCategories as $taxCategory)
                                <option value="{{ $taxCategory->id }}" {{ old('tax_category_id', $item->tax_category_id) == $taxCategory->id ? 'selected' : '' }}>{{ $taxCategory->name }} ({{ $taxCategory->rate }}%)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_taxable" value="1" class="form-check-input" id="is_taxable" {{ old('is_taxable', $item->is_taxable) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_taxable">Taxable</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <hr>
                    <h5>Clinical Auto-Charge</h5>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_clinically_chargeable" value="1" class="form-check-input" id="is_clinically_chargeable" {{ old('is_clinically_chargeable', $item->is_clinically_chargeable) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_clinically_chargeable">Clinically chargeable</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clinical Event Type</label>
                        <select name="clinical_event_type" class="form-control">
                            <option value="">-</option>
                            <option value="encounter" {{ old('clinical_event_type', $item->clinical_event_type) == 'encounter' ? 'selected' : '' }}>Encounter</option>
                            <option value="clinical_order" {{ old('clinical_event_type', $item->clinical_event_type) == 'clinical_order' ? 'selected' : '' }}>Clinical Order</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clinical Event Key</label>
                        <input type="text" name="clinical_event_key" value="{{ old('clinical_event_key', $item->clinical_event_key) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control">{{ old('description', $item->description) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.items.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
@stop
