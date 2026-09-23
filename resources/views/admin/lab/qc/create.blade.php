@extends('layouts.adminlte')

@section('page_title', 'Record QC Run')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Record QC Run</h3></div>
        <form action="{{ route('admin.lab.qc.store') }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">QC Material</label>
                        <select name="qc_material_id" class="form-control @error('qc_material_id') is-invalid @enderror" required>
                            <option value="">Select Material</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}" {{ old('qc_material_id') == $material->id ? 'selected' : '' }}>{{ $material->name }} ({{ $material->level }})</option>
                            @endforeach
                        </select>
                        @error('qc_material_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Test</label>
                        <select name="test_id" class="form-control @error('test_id') is-invalid @enderror" required>
                            <option value="">Select Test</option>
                            @foreach($tests as $test)
                                <option value="{{ $test->id }}" {{ old('test_id') == $test->id ? 'selected' : '' }}>{{ $test->name }}</option>
                            @endforeach
                        </select>
                        @error('test_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expected Low</label>
                        <input type="number" step="0.0001" name="expected_low" value="{{ old('expected_low') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expected High</label>
                        <input type="number" step="0.0001" name="expected_high" value="{{ old('expected_high') }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observed Value</label>
                        <input type="number" step="0.0001" name="observed_value" value="{{ old('observed_value') }}" class="form-control @error('observed_value') is-invalid @enderror" required>
                        @error('observed_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.lab.qc.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save QC Run</button>
            </div>
        </form>
    </div>
@stop
