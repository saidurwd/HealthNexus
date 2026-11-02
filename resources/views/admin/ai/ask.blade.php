@extends('layouts.adminlte')

@section('page_title', $title)

@section('page_content')
    @include('admin.ai._flash')
    <p class="text-muted">{{ $help }}</p>
    <div class="alert alert-light border small"><i class="bi bi-shield-check"></i> {{ __('What you get is a draft marked "AI-generated — needs review". Nothing is written into any record.') }}</div>
    <form method="POST" action="{{ route(['summary' => 'admin.ai.summary.run', 'notes' => 'admin.ai.notes.run', 'ask' => 'admin.ai.ask.run', 'figures' => 'admin.ai.figures.run', 'forecast' => 'admin.ai.forecast.run'][$mode]) }}" class="card"><div class="card-body row g-3">@csrf
        @if($mode === 'summary')<div class="col-md-4"><label class="form-label">{{ __('Patient number') }}</label><input name="patient_no" class="form-control" value="{{ old('patient_no') }}" required autofocus></div><div class="col-md-3"><label class="form-label">{{ __('How far back (days)') }}</label><input type="number" name="days" class="form-control" min="7" max="3650" value="{{ old('days', 365) }}"></div>
        @elseif($mode === 'notes')<div class="col-md-5"><label class="form-label">{{ __('Visit number') }}</label><input name="encounter_no" class="form-control" value="{{ old('encounter_no') }}" required autofocus></div>
        @elseif($mode === 'forecast')<div class="col-md-6"><label class="form-label">{{ __('Item') }}</label><select name="item_id" class="form-select">@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->code }} · {{ $i->name }}</option>@endforeach</select></div>
        @else<div class="col-12"><label class="form-label">{{ __('Your question') }}</label><textarea name="question" class="form-control" rows="2" maxlength="500" required autofocus>{{ old('question') }}</textarea></div>@endif
        <div class="col-12"><button class="btn btn-primary">{{ __('Ask the assistant') }}</button></div></div></form>
@stop
