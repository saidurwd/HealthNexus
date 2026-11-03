@extends('layouts.adminlte')

@section('page_title', $o->title)

@section('page_content')
    @include('admin.ai._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="alert alert-warning d-flex justify-content-between align-items-center"><strong><i class="bi bi-robot"></i> {{ __($o->label) }}</strong><x-ui.status-badge :status="$o->status" /></div>
    @if($patient)<div class="alert alert-light border small mb-3"><strong>{{ __('Patient') }}:</strong> {{ $patient->first_name }} {{ $patient->last_name }} · {{ $patient->enterprise_patient_no }}</div>@endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-body" style="white-space: pre-line">{{ $o->text }}</div>
                @if($o->uncertainty)<div class="card-footer small text-muted">{{ $o->uncertainty }}</div>@endif</div>
            @if($o->suppressed > 0)<div class="small text-muted mb-3"><i class="bi bi-funnel"></i> {{ __(':n line(s) the assistant wrote were removed because they were not supported by the record or told someone to act.', ['n' => $o->suppressed]) }}</div>@endif
            @if($o->status === 'accepted' && $o->edited_text)<div class="card border-success mb-3"><div class="card-header"><h3 class="card-title">{{ __('The reviewer\'s version') }}</h3></div><div class="card-body" style="white-space: pre-line">{{ $o->edited_text }}</div></div>@endif
            @if($o->sources)<div class="card"><div class="card-header"><h3 class="card-title">{{ __('Where it came from') }}</h3></div><ul class="list-group list-group-flush small">@foreach($o->sources as $s)<li class="list-group-item"><code>{{ $s['ref'] }}</code> {{ $s['label'] }}</li>@endforeach</ul></div>@endif
        </div>
        <div class="col-lg-4">
            @if($canReview)
            <form method="POST" action="{{ route('admin.ai.outputs.accept', $o->public_id) }}" class="card border-success mb-3"><div class="card-header"><h3 class="card-title">{{ __('Review') }}</h3></div><div class="card-body">@csrf
                @if($patient)<label class="form-label small">{{ __('Type the patient number to confirm this is the right chart') }}</label><input name="patient_no" class="form-control form-control-sm mb-2" required autocomplete="off" placeholder="{{ $patient->enterprise_patient_no }}">@endif
                <label class="form-label small">{{ __('Your version (optional: edit what you will use)') }}</label><textarea name="edited_text" class="form-control form-control-sm mb-2" rows="6">{{ $o->text }}</textarea>
                <input name="note" class="form-control form-control-sm mb-2" placeholder="{{ __('Note') }}"><button class="btn btn-sm btn-success">{{ __('Accept') }}</button>
                <div class="form-text">{{ __('Accepting does not write anything into the record.') }}</div></div></form>
            <form method="POST" action="{{ route('admin.ai.outputs.reject', $o->public_id) }}" class="card mb-3"><div class="card-body">@csrf<input name="reason" class="form-control form-control-sm mb-2" placeholder="{{ __('Why reject it?') }}" required><button class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></div></form>
            @elseif($o->status === 'draft' && $o->kind !== 'none')<div class="alert alert-light border small">{{ __('A clinician with the right must review this draft.') }}</div>@endif
            @if($o->reviewer)<div class="small text-muted mb-3">{{ __('Reviewed by') }} {{ $o->reviewer->name }} · {{ $o->reviewed_at?->setTimezone('Asia/Dhaka')->format('d M H:i') }} @if($o->review_note)<br>{{ $o->review_note }}@endif</div>@endif
            <form method="POST" action="{{ route('admin.ai.outputs.feedback', $o->public_id) }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Tell us how it did') }}</h3></div><div class="card-body row g-1">@csrf
                <div class="col-12"><select name="rating" class="form-select form-select-sm"><option value="useful">{{ __('Useful') }}</option><option value="not_useful">{{ __('Not useful') }}</option></select></div>
                <div class="col-12"><select name="error_type" class="form-select form-select-sm"><option value="">{{ __('Nothing was wrong') }}</option>@foreach(['wrong_fact', 'missing', 'wrong_patient', 'unsafe', 'other'] as $e)<option value="{{ $e }}">{{ $H($e) }}</option>@endforeach</select></div>
                <div class="col-12"><input name="comment" class="form-control form-control-sm" placeholder="{{ __('What happened?') }}"></div><div class="col-12"><button class="btn btn-sm btn-outline-secondary">{{ __('Send') }}</button></div></div></form>
        </div>
    </div>
@stop
