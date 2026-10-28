@extends('layouts.adminlte')

@section('page_title', __('When results appear for patients'))

@section('page_content')
    @include('admin.portal._flash')
    <p class="text-muted small">{{ __('One rule for everything, and stricter rules for a kind of test. Where a report has several tests the strictest rule wins. Tests with words like HIV, pregnancy, genetic or biopsy are always held for a clinician. Rules apply to reports made after you save them.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Applies to') }}</th><th>{{ __('Shown to the patient') }}</th><th>{{ __('Delay') }}</th><th>{{ __('Sensitive') }}</th><th>{{ __('Note') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>@if($r->scope_type === 'default'){{ __('Everything else') }}@elseif($r->scope_type === 'radiology'){{ __('Radiology reports') }}@else{{ __('Lab category') }} {{ $r->scope_key }}@endif</td><td>{{ __(\Illuminate\Support\Str::headline($r->mode)) }}</td><td>{{ $r->mode === 'delayed' ? $r->delay_hours.' h' : '' }}</td><td>{{ $r->sensitive ? __('Yes') : '' }}</td><td class="small text-muted">{{ $r->note }}</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No rules yet: the hospital default applies (see Portal settings).') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('portal.release.policy')<form method="POST" action="{{ route('admin.portal.policies.save') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Add or change a rule') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><select name="scope_type" class="form-select"><option value="default">{{ __('Everything else') }}</option><option value="radiology">{{ __('Radiology reports') }}</option><option value="lab_category">{{ __('A lab category') }}</option></select></div>
        <div class="col-md-3"><select name="scope_key" class="form-select"><option value="">—</option>@foreach($categories as $c)<option value="{{ $c->code }}">{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="mode" class="form-select">@foreach(['immediate' => 'Straight away', 'after_review' => 'After a clinician releases it', 'delayed' => 'After a delay', 'never' => 'Not online'] as $k => $t)<option value="{{ $k }}">{{ __($t) }}</option>@endforeach</select></div>
        <div class="col-md-3"><input type="number" name="delay_hours" class="form-control" placeholder="{{ __('Delay in hours') }}" min="0" max="720"></div>
        <div class="col-md-3"><div class="form-check"><input type="checkbox" class="form-check-input" name="sensitive" value="1" id="sens"><label class="form-check-label" for="sens">{{ __('Sensitive') }}</label></div></div>
        <div class="col-md-6"><input name="note" class="form-control" placeholder="{{ __('Note') }}"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save rule') }}</button></div></form>@endcan
@stop
