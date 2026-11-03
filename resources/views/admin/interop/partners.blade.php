@extends('layouts.adminlte')

@section('page_title', __('Partners'))

@section('page_content')
    @include('admin.interop._flash')
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Code') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Direction') }}</th><th>{{ __('Profile') }}</th><th>{{ __('Needs patient consent') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Last 7 days') }}</th></tr></thead>
        <tbody>@forelse($rows as $p)<tr><td><a href="{{ route('admin.interop.partners.show', $p->public_id) }}">{{ $p->name }}</a></td><td>{{ $p->code }}</td><td>{{ __(\Illuminate\Support\Str::headline($p->kind)) }}</td><td>{{ __(\Illuminate\Support\Str::headline($p->direction)) }}</td><td>{{ $p->profile }}</td><td>{{ $p->requires_consent ? __('Yes').' ('.$p->consent_type.')' : __('No') }}</td><td><x-ui.status-badge :status="$p->status" /></td><td class="text-end">{{ collect($stats[$p->id] ?? [])->sum('c') }}</td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('No partners yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('interoperability.partner.manage')<form method="POST" action="{{ route('admin.interop.partners.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Add a partner') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><label class="form-label" for="name">{{ __('Name') }}</label><input id="name" name="name" class="form-control" required value="{{ old('name') }}"></div>
        <div class="col-md-2"><label class="form-label" for="code">{{ __('Code') }}</label><input id="code" name="code" class="form-control" required value="{{ old('code') }}"></div>
        <div class="col-md-3"><label class="form-label" for="kind">{{ __('Kind') }}</label><select id="kind" name="kind" class="form-select">@foreach($kinds as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="direction">{{ __('Direction') }}</label><select id="direction" name="direction" class="form-select"><option value="both">{{ __('Both') }}</option><option value="in">{{ __('They send to us') }}</option><option value="out">{{ __('We send to them') }}</option></select></div>
        <div class="col-md-3"><label class="form-label" for="profile">{{ __('Rules the data must follow') }}</label><select id="profile" name="profile" class="form-select">@foreach($profiles as $pr)<option value="{{ $pr }}">{{ $pr }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="consent_type">{{ __('Consent the patient must have given') }}</label><input id="consent_type" name="consent_type" class="form-control" value="{{ old('consent_type', 'data_sharing') }}"></div>
        <div class="col-md-3"><label class="form-label d-block">{{ __('Patient consent is needed') }}</label><input type="hidden" name="requires_consent" value="0"><div class="form-check"><input type="checkbox" class="form-check-input" name="requires_consent" value="1" id="rc" checked><label for="rc" class="form-check-label">{{ __('Yes') }}</label></div></div>
        <div class="col-md-3"><label class="form-label" for="ae">{{ __('Scanner AE title (scanners)') }}</label><input id="ae" name="ae_title" class="form-control" maxlength="16"></div>
        <div class="col-md-3"><label class="form-label" for="sa">{{ __('Their HL7 application name (MSH-3)') }}</label><input id="sa" name="sending_application" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="sf">{{ __('Their HL7 facility name (MSH-4)') }}</label><input id="sf" name="sending_facility" class="form-control"></div>
        <div class="col-md-6"><label class="form-label" for="contact">{{ __('Who to contact') }}</label><input id="contact" name="contact" class="form-control"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Add') }}</button></div></form>@endcan
@stop
