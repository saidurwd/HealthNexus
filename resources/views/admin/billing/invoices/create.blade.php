@extends('layouts.adminlte')

@section('page_title', 'New Invoice')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Invoice</h3></div>
        <form action="{{ route('admin.billing.invoices.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Patient ID</label>
                    <input type="number" name="patient_id" value="{{ old('patient_id', $patient?->id) }}" class="form-control @error('patient_id') is-invalid @enderror" required>
                    @if($patient)
                        <small class="text-muted">{{ $patient->full_name }} ({{ $patient->enterprise_patient_no }})</small>
                    @endif
                    @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Encounter ID (optional)</label>
                    <input type="number" name="encounter_id" value="{{ old('encounter_id') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Invoice Type</label>
                    <select name="invoice_type" class="form-control @error('invoice_type') is-invalid @enderror" required>
                        @foreach(['opd','ipd','diagnostic','pharmacy','procedure','corporate','insurance','advance','other'] as $type)
                            <option value="{{ $type }}" {{ old('invoice_type', 'opd') == $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                        @endforeach
                    </select>
                    @error('invoice_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Corporate (optional)</label>
                    <input type="number" name="corporate_id" value="{{ old('corporate_id') }}" class="form-control" placeholder="Corporate ID">
                </div>
                <div class="mb-3">
                    <label class="form-label">Insurance Policy (optional)</label>
                    <input type="number" name="insurance_policy_id" value="{{ old('insurance_policy_id') }}" class="form-control" placeholder="Insurance Policy ID">
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Draft Invoice</button>
            </div>
        </form>
    </div>
@stop
