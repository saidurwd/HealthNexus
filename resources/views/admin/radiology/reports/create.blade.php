@extends('layouts.adminlte')

@section('page_title', 'New Radiology Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Report — {{ $examination->orderItem->procedure?->name }}</h3></div>
        <form action="{{ route('admin.radiology.reports.store', $examination) }}" method="post">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Template (optional)</label>
                    <select name="template_id" class="form-control">
                        <option value="">None</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Clinical Indication</label>
                    <textarea name="clinical_indication" class="form-control">{{ old('clinical_indication', $examination->orderItem->radiologyOrder->clinical_indication) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Technique</label>
                    <textarea name="technique" class="form-control">{{ old('technique') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Findings</label>
                    <textarea name="findings" rows="6" class="form-control">{{ old('findings') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Impression</label>
                    <textarea name="impression" rows="3" class="form-control">{{ old('impression') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Recommendation</label>
                    <textarea name="recommendation" class="form-control">{{ old('recommendation') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.radiology.examinations.show', $examination) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Draft</button>
            </div>
        </form>
    </div>
@stop
