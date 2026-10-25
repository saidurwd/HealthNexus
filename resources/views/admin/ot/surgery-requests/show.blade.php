@extends('layouts.adminlte')

@section('page_title', 'Surgery Request')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Surgery Request — {{ $surgeryRequest->patient?->full_name }}</h3></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Procedure</dt><dd class="col-sm-9">{{ $surgeryRequest->procedure?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Specialty</dt><dd class="col-sm-9">{{ $surgeryRequest->specialty?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Priority</dt><dd class="col-sm-9">{{ ucfirst($surgeryRequest->priority) }}</dd>
                <dt class="col-sm-3">Indication</dt><dd class="col-sm-9">{{ $surgeryRequest->indication ?? '—' }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-info">{{ ucwords(str_replace('_',' ',$surgeryRequest->status)) }}</span></dd>
            </dl>
        </div>
        <div class="card-footer">
            @if(in_array($surgeryRequest->status, ['requested','pending_approval']))
                @can('ot.surgery.approve')
                    <form action="{{ route('admin.ot.surgery-requests.approve', $surgeryRequest) }}" method="post" class="d-inline">@csrf<button class="btn btn-success">Approve</button></form>
                    <form action="{{ route('admin.ot.surgery-requests.reject', $surgeryRequest) }}" method="post" class="d-inline">@csrf<input name="reason" placeholder="Rejection reason" required><button class="btn btn-danger">Reject</button></form>
                @endcan
            @endif
            @if($surgeryRequest->status === 'approved' && ! $surgeryRequest->surgery)
                @can('ot.surgery.create')
                    <a href="{{ route('admin.ot.surgeries.create', ['surgery_request_id' => $surgeryRequest->id]) }}" class="btn btn-primary">Schedule as Surgical Case</a>
                @endcan
            @endif
            @if($surgeryRequest->surgery)
                <a href="{{ route('admin.ot.surgeries.show', $surgeryRequest->surgery) }}" class="btn btn-info">View Surgical Case</a>
            @endif
        </div>
    </div>
@stop
