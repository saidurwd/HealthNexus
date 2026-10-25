@extends('layouts.adminlte')

@section('page_title', 'ICU Admission Request')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ $icuRequest->patient?->full_name }}</h3></div>
        <div class="card-body">
            <p><strong>{{ __('Status:') }}</strong> <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $icuRequest->status)) }}</span></p>
            <p><strong>{{ __('Priority:') }}</strong> {{ ucwords($icuRequest->priority) }}</p>
            <p><strong>{{ __('Emergency:') }}</strong> {{ $icuRequest->is_emergency ? 'Yes' : 'No' }}</p>
            <p><strong>{{ __('Reason:') }}</strong> {{ $icuRequest->admission_reason ?? '—' }}</p>

            @if(in_array($icuRequest->status, ['requested', 'pending_approval']))
                <form method="POST" action="{{ route('admin.icu.admission-requests.approve', $icuRequest) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">{{ __('Accept') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.icu.admission-requests.reject', $icuRequest) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="reason" value="Rejected by reviewer">
                    <button type="submit" class="btn btn-danger">{{ __('Reject') }}</button>
                </form>
            @endif

            @if($icuRequest->status === 'accepted' && ! $icuRequest->episode)
                <a href="{{ route('admin.icu.episodes.create', ['admission_request_id' => $icuRequest->id]) }}" class="btn btn-primary">{{ __('Admit to ICU') }}</a>
            @endif

            @if($icuRequest->episode)
                <a href="{{ route('admin.icu.episodes.show', $icuRequest->episode) }}" class="btn btn-info">{{ __('View ICU Episode') }}</a>
            @endif
        </div>
    </div>
@stop
