@extends('layouts.adminlte')

@section('page_title', 'Receive Stock')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Receive Stock (New Batch)</h3></div>
        <form method="POST" action="{{ route('admin.pharmacy.stock.receive') }}">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Store</label>
                        <select name="store_id" class="form-control @error('store_id') is-invalid @enderror" required>
                            <option value="">Select Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @error('store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medication</label>
                        <select name="medication_id" class="form-control @error('medication_id') is-invalid @enderror" required>
                            <option value="">Select Medication</option>
                            @foreach($medications as $medication)
                                <option value="{{ $medication->id }}">{{ $medication->name }}</option>
                            @endforeach
                        </select>
                        @error('medication_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Number</label>
                        <input type="text" name="batch_number" value="{{ old('batch_number') }}" class="form-control @error('batch_number') is-invalid @enderror" required>
                        @error('batch_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" min="1" name="quantity" value="{{ old('quantity') }}" class="form-control @error('quantity') is-invalid @enderror" required>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Manufacturing Date</label>
                        <input type="date" name="manufacturing_date" value="{{ old('manufacturing_date') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-control @error('expiry_date') is-invalid @enderror" required>
                        @error('expiry_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selling Price</label>
                        <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier Reference</label>
                        <input type="text" name="supplier_reference" value="{{ old('supplier_reference') }}" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.stock.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Receive Stock</button>
            </div>
        </form>
    </div>
@stop
