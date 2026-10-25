@extends('layouts.adminlte')

@section('page_title', 'Preoperative — '.$surgery->surgery_number)

@section('page_content')
    @if(!empty($mismatches))
        <div class="alert alert-danger">
            <strong>Verification mismatches:</strong>
            <ul class="mb-0">@foreach($mismatches as $m)<li>{{ $m }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h3 class="card-title">Consent</h3></div>
        <div class="card-body">
            @if($surgery->consent)
                <p>Obtained — version {{ $surgery->consent->version }}, {{ $surgery->consent->granted_at }}</p>
            @else
                @can('ot.consent.create')
                <form action="{{ route('admin.ot.consent.store', $surgery) }}" method="post">
                    @csrf
                    <textarea name="notes" class="form-control mb-2" placeholder="Consent notes"></textarea>
                    <input name="witness_name" class="form-control mb-2" placeholder="Witness name (optional)">
                    <input name="language" class="form-control mb-2" placeholder="Language (optional)">
                    <button class="btn btn-primary">Record Consent</button>
                </form>
                @endcan
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Preoperative Assessment</h3></div>
        <div class="card-body">
            @if($surgery->preoperativeAssessment)
                <p>Status: {{ ucfirst($surgery->preoperativeAssessment->status) }}</p>
                @if($surgery->preoperativeAssessment->status === 'draft')
                    @can('ot.preop.complete')
                    <form action="{{ route('admin.ot.preoperative.assessment.finalize', $surgery->preoperativeAssessment) }}" method="post">@csrf<button class="btn btn-sm btn-success">Finalize</button></form>
                    @endcan
                @endif
            @else
                @can('ot.preop.create')
                <form action="{{ route('admin.ot.preoperative.assessment.store', $surgery) }}" method="post">@csrf<button class="btn btn-primary">Start Assessment</button></form>
                @endcan
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Preoperative Checklist ({{ ucfirst($checklist->status) }})</h3></div>
        <div class="card-body">
            @foreach($checklist->items as $item)
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span>{{ $item->label }} @if($item->is_required)<span class="text-danger">*</span>@endif</span>
                    @if($item->completed_at)
                        <span class="badge bg-success">{{ $item->response ?? 'Exception: '.$item->exception_reason }}</span>
                    @else
                        @can('ot.preop.create')
                        <form action="{{ route('admin.ot.preoperative.checklist-items.complete', $item) }}" method="post" class="d-inline">
                            @csrf
                            <input name="response" placeholder="Response" size="12">
                            <button class="btn btn-xs btn-success">Confirm</button>
                        </form>
                        @endcan
                    @endif
                </div>
            @endforeach
            @if($checklist->status !== 'completed')
                @can('ot.preop.complete')
                <form action="{{ route('admin.ot.preoperative.checklist.complete', $checklist) }}" method="post" class="mt-2">@csrf<button class="btn btn-success">Complete Checklist</button></form>
                @endcan
            @endif
        </div>
    </div>
@stop
