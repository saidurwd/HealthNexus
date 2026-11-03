@extends('layouts.adminlte')

@section('page_title', __('Patient groups'))

@section('page_content')
    @include('admin.crm._flash')
    <p class="text-muted small">{{ __('A group is a way to find patients to write to. A group about a diagnosis needs its legal basis written down and a second person to approve it, and only patients who agreed to clinical messages are included.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Conditions') }}</th><th class="text-end">{{ __('Patients') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $s)<tr><td>{{ $s->name }}@if($s->description)<div class="small text-muted">{{ $s->description }}</div>@endif @if($s->clinical_basis)<div class="small text-warning"><i class="bi bi-shield-lock"></i> {{ $s->clinical_basis }}</div>@endif</td><td class="small">@foreach($s->criteria as $k => $v){{ __(\Illuminate\Support\Str::headline($k)) }}: {{ is_bool($v) ? ($v ? __('Yes') : __('No')) : $v }}@if(! $loop->last) · @endif @endforeach @if(! $s->criteria){{ __('Everyone') }}@endif</td><td class="text-end">{{ $s->last_count ?? '—' }}</td><td><x-ui.status-badge :status="$s->status" /></td>
            <td><form method="POST" action="{{ route('admin.crm.segments.action', $s->public_id) }}" class="d-flex gap-1">@csrf @if($s->status !== 'retired')<button name="action" value="count" class="btn btn-sm btn-outline-secondary">{{ __('Count') }}</button>@endif @can('crm.segment.approve')@if($s->status === 'draft')<button name="action" value="activate" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button>@endif @endcan @can('crm.segment.manage')@if($s->status !== 'retired')<button name="action" value="retire" class="btn btn-sm btn-outline-danger">{{ __('Retire') }}</button>@endif @endcan</form></td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No groups yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('crm.segment.manage')<form method="POST" action="{{ route('admin.crm.segments.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('New group') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required></div><div class="col-md-8"><input name="description" class="form-control" placeholder="{{ __('What it is for') }}"></div>
        <div class="col-md-2"><input type="number" name="age_from" class="form-control" placeholder="{{ __('Age from') }}"></div><div class="col-md-2"><input type="number" name="age_to" class="form-control" placeholder="{{ __('Age to') }}"></div>
        <div class="col-md-2"><select name="sex" class="form-select"><option value="">{{ __('Any sex') }}</option><option value="F">{{ __('Female') }}</option><option value="M">{{ __('Male') }}</option></select></div>
        <div class="col-md-3"><input type="number" name="last_visit_before_days" class="form-control" placeholder="{{ __('Not seen for (days)') }}"></div><div class="col-md-3"><input type="number" name="last_visit_within_days" class="form-control" placeholder="{{ __('Seen in the last (days)') }}"></div>
        <div class="col-md-2"><input type="number" name="visits_at_least" class="form-control" placeholder="{{ __('Visits at least') }}"></div>
        <div class="col-md-2"><select name="has_portal" class="form-select"><option value="">{{ __('Portal: any') }}</option><option value="1">{{ __('Uses the portal') }}</option><option value="0">{{ __('Does not use the portal') }}</option></select></div>
        <div class="col-md-2"><select name="branch_id" class="form-select"><option value="">{{ __('Any branch') }}</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="language" class="form-select"><option value="">{{ __('Any language') }}</option><option value="bn">বাংলা</option><option value="en">English</option></select></div>
        <div class="col-md-3"><input name="problem_code_prefix" class="form-control" placeholder="{{ __('Diagnosis code starts with (e.g. E11)') }}"></div><div class="col-md-9"><input name="clinical_basis" class="form-control" placeholder="{{ __('Legal basis, needed for a diagnosis group') }}"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form>@endcan
@stop
