@extends('layouts.adminlte')

@section('page_title', 'Edit Price List')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Edit Price List</h3></div>
        <form action="{{ route('admin.billing.price-lists.update', $priceList) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $priceList->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code', $priceList->code) }}" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', $priceList->currency) }}" maxlength="3" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <input type="number" name="priority" value="{{ old('priority', $priceList->priority) }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="active" {{ old('status', $priceList->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $priceList->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Effective From</label>
                        <input type="date" name="effective_from" value="{{ old('effective_from', optional($priceList->effective_from)->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" value="{{ old('effective_to', optional($priceList->effective_to)->format('Y-m-d')) }}" class="form-control">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.price-lists.show', $priceList) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Price List</button>
            </div>
        </form>
    </div>
@stop
