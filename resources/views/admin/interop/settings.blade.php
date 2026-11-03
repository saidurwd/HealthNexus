@extends('layouts.adminlte')

@section('page_title', __('Integration settings'))

@section('page_content')
    @include('admin.interop._flash')
    <p class="text-muted small">{{ __('Starting values. The integration lead and the privacy officer should confirm them.') }}</p>
    <form method="POST" action="{{ route('admin.interop.settings.save') }}" class="card"><div class="card-body row g-3">@csrf
        <div class="col-md-3"><label class="form-label" for="a">{{ __('Tries before a message is given up') }}</label><input id="a" type="number" name="max_attempts" class="form-control" value="{{ $v['max_attempts'] }}" min="1" max="20"></div>
        <div class="col-md-5"><label class="form-label" for="b">{{ __('Seconds to wait before each new try') }}</label><input id="b" name="backoff" class="form-control" value="{{ $v['backoff'] }}"></div>
        <div class="col-md-4"><label class="form-label" for="c">{{ __('Old secret keeps working after rotation (hours)') }}</label><input id="c" type="number" name="overlap_hours" class="form-control" value="{{ $v['overlap_hours'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="d">{{ __('Partner tokens last (days)') }}</label><input id="d" type="number" name="token_days" class="form-control" value="{{ $v['token_days'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="e">{{ __('Keep message content, delivered (days)') }}</label><input id="e" type="number" name="processed_days" class="form-control" value="{{ $v['processed_days'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="f">{{ __('Keep message content, failed (days)') }}</label><input id="f" type="number" name="dead_days" class="form-control" value="{{ $v['dead_days'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="g">{{ __('Keep the who-saw-what log (days)') }}</label><input id="g" type="number" name="access_days" class="form-control" value="{{ $v['access_days'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="h">{{ __('Records per page in the FHIR API') }}</label><input id="h" type="number" name="page_size" class="form-control" value="{{ $v['page_size'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="i">{{ __('A signed notice is accepted within (seconds)') }}</label><input id="i" type="number" name="tolerance" class="form-control" value="{{ $v['tolerance'] }}"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form>
@stop
