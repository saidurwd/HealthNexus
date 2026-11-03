@extends('layouts.adminlte')

@section('page_title', $s->form->title)

@section('page_content')
    @include('admin.crm._flash')
    <x-patient-banner :patient="$s->patient" />
    <div class="card"><div class="card-header">{{ __('Answered') }} {{ $s->created_at->format('d M Y H:i') }} · {{ __('version') }} {{ $s->form_version }} <x-ui.status-badge :status="$s->status" /></div>
        <dl class="row p-3 mb-0">@foreach($s->answers['fields'] as $f)<dt class="col-md-4">{{ $f['label'] }}</dt><dd class="col-md-8">{{ $s->answers['values'][$f['key']] ?? '—' }}</dd>@endforeach</dl>
        @can('crm.form.manage')@if($s->status === 'submitted')<div class="card-footer"><form method="POST" action="{{ route('admin.crm.forms.review', $s->public_id) }}">@csrf<button class="btn btn-primary">{{ __('I have looked at this') }}</button></form></div>@endif @endcan</div>
@stop
