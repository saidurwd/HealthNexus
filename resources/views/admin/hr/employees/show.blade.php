@extends('layouts.adminlte')

@section('page_title', $employee->fullName())

@section('page_content')
    @include('admin.hr._flash')
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center small">
        <strong>{{ $employee->employee_no }}</strong> <x-ui.status-badge :status="$employee->status" />
        <span>{{ $employee->department?->name ?? __('No department') }}</span> <span>{{ $employee->job?->title ?? __('No job') }}</span> <span>{{ __('Reports to') }} {{ $employee->manager?->fullName() ?? '—' }}</span>
        <span>{{ __('Joined') }} {{ $employee->joined_on?->toDateString() ?? '—' }}</span> @if($employee->separated_on)<span class="text-danger">{{ __('Left') }} {{ $employee->separated_on->toDateString() }}: {{ $employee->separation_reason }}</span>@endif
        <span>{{ __('Login') }}: {{ $employee->user?->email ?? __('not linked') }}</span>
        @if($gaps)<span class="text-danger">{{ __('Licence missing or lapsed') }}: {{ implode(', ', $gaps) }}</span>@endif
        @if($sensitive)<span class="w-100 text-muted">{{ __('Private details') }}: {{ __('National ID') }} {{ $mask($employee->national_id) }} · {{ __('Tax ID') }} {{ $mask($employee->tin) }} · {{ $employee->bank_name ?: __('Bank') }} {{ $mask($employee->bank_account) }} · {{ __('Wallet') }} {{ $mask($employee->mobile_wallet) }}</span>@endif
    </div></div>

    <div class="row g-3">
        <div class="col-lg-7">
            @can('hr.employee.manage')
            <form method="POST" action="{{ route('admin.hr.employees.update', $employee) }}" class="card">@csrf
                <div class="card-header"><h3 class="card-title">{{ __('Details') }}</h3></div>
                <div class="card-body row g-2">
                    <div class="col-md-4"><label class="form-label small">{{ __('First name') }}</label><input name="first_name" value="{{ $employee->first_name }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Last name') }}</label><input name="last_name" value="{{ $employee->last_name }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Category') }}</label><select name="category" class="form-select">@foreach($categories as $c)<option value="{{ $c }}" @selected($employee->category === $c)>{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Department') }}</label><select name="department_id" class="form-select"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected($employee->department_id === $d->id)>{{ $d->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Job') }}</label><select name="job_id" class="form-select"><option value="">—</option>@foreach($jobs as $j)<option value="{{ $j->id }}" @selected($employee->job_id === $j->id)>{{ $j->title }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Manager') }}</label><select name="manager_id" class="form-select"><option value="">—</option>@foreach($managers as $m)<option value="{{ $m->id }}" @selected($employee->manager_id === $m->id)>{{ $m->first_name }} {{ $m->last_name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Phone') }}</label><input name="phone" value="{{ $employee->phone }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Email') }}</label><input name="email" value="{{ $employee->email }}" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small">{{ __('Weekly off') }}</label><select name="weekly_off" class="form-select">@foreach(['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'] as $d)<option value="{{ $d }}" @selected($employee->weekly_off === $d)>{{ strtoupper($d) }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label small">{{ __('Device no.') }}</label><input name="device_code" value="{{ $employee->device_code }}" class="form-control"></div>
                    <div class="col-md-8"><label class="form-label small">{{ __('Address') }}</label><input name="address" value="{{ $employee->address }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small">{{ __('Emergency contact') }}</label><input name="emergency_contact" value="{{ $employee->emergency_contact }}" class="form-control"></div>
                    @if($sensitive)
                        <div class="col-12"><hr class="my-1"><div class="small text-muted">{{ __('These are stored encrypted and only shown masked; leave a box empty to keep what is there.') }}</div></div>
                        <div class="col-md-3"><label class="form-label small">{{ __('National ID') }} <span class="text-muted">{{ $mask($employee->national_id) }}</span></label><input name="national_id" class="form-control" autocomplete="off"></div>
                        <div class="col-md-3"><label class="form-label small">{{ __('Tax ID') }} <span class="text-muted">{{ $mask($employee->tin) }}</span></label><input name="tin" class="form-control" autocomplete="off"></div>
                        <div class="col-md-2"><label class="form-label small">{{ __('Bank') }}</label><input name="bank_name" value="{{ $employee->bank_name }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label small">{{ __('Account') }} <span class="text-muted">{{ $mask($employee->bank_account) }}</span></label><input name="bank_account" class="form-control" autocomplete="off"></div>
                        <div class="col-md-2"><label class="form-label small">{{ __('Wallet') }} <span class="text-muted">{{ $mask($employee->mobile_wallet) }}</span></label><input name="mobile_wallet" class="form-control" autocomplete="off"></div>
                    @endif
                    <div class="col-md-9"><input name="reason" class="form-control" placeholder="{{ __('Why the change? (kept in the history)') }}" maxlength="300"></div>
                    <div class="col-md-3"><button class="btn btn-danger w-100">{{ __('Save') }}</button></div>
                </div></form>
            @endcan

            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Checklists') }}</h3></div>
                @forelse($checklists as $c)
                    <div class="card-body border-top small"><div class="fw-semibold mb-1">{{ __(\Illuminate\Support\Str::headline($c->kind)) }} <x-ui.status-badge :status="$c->status" /></div>
                        @foreach($c->items as $i)<div class="d-flex justify-content-between align-items-center py-1"><span class="{{ $i->done_at ? 'text-decoration-line-through text-muted' : '' }}">{{ $i->title }} @unless($i->required)<em class="text-muted">({{ __('optional') }})</em>@endunless</span>
                            @if(! $i->done_at && $c->status === 'open')@can('hr.onboarding.manage')<form method="POST" action="{{ route('admin.hr.checklist.tick', $i) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('Done') }}</button></form>@endcan @else<span class="text-muted">{{ $i->done_at?->format('d M') }}</span>@endif</div>@endforeach</div>
                @empty<div class="card-body text-muted small">{{ __('No checklist yet.') }}</div>@endforelse
                @can('hr.onboarding.manage')<form method="POST" action="{{ route('admin.hr.employees.checklists', $employee) }}" class="card-footer d-flex gap-2">@csrf<select name="kind" class="form-select form-select-sm w-auto"><option value="onboarding">{{ __('Onboarding') }}</option><option value="offboarding">{{ __('Offboarding') }}</option></select><button class="btn btn-sm btn-outline-primary">{{ __('Open a checklist') }}</button></form>@endcan</div>

            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('History') }}</h3></div>
                <ul class="list-group list-group-flush small">@foreach($history as $h)<li class="list-group-item"><strong>{{ __(\Illuminate\Support\Str::headline($h->change_type)) }}</strong> · {{ $h->effective_on->toDateString() }} <span class="text-muted">{{ $h->reason }}</span></li>@endforeach</ul></div>
        </div>

        <div class="col-lg-5">
            @can('hr.employee.manage')
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Status') }}</h3></div>
                <form method="POST" action="{{ route('admin.hr.employees.status', $employee) }}" class="card-body d-flex flex-wrap gap-2">@csrf
                    <input type="date" name="date" value="{{ today()->toDateString() }}" class="form-control form-control-sm w-auto"><input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('Reason') }}">
                    @foreach(['onboarding' => ['active'], 'active' => ['on_leave', 'suspended'], 'on_leave' => ['active'], 'suspended' => ['active']][$employee->status] ?? [] as $to)<button name="action" value="{{ $to }}" class="btn btn-sm btn-outline-primary">{{ __(\Illuminate\Support\Str::headline($to)) }}</button>@endforeach
                    @can('hr.employee.separate')
                        @if(in_array($employee->status, ['active', 'on_leave', 'suspended']))@foreach(['resigned', 'terminated', 'retired'] as $to)<button name="action" value="{{ $to }}" class="btn btn-sm btn-outline-danger">{{ __(\Illuminate\Support\Str::headline($to)) }}</button>@endforeach @endif
                        @if(in_array($employee->status, ['resigned', 'terminated', 'retired']))<button name="action" value="rehire" class="btn btn-sm btn-outline-success">{{ __('Re-hire') }}</button>@endif
                    @endcan
                </form>
                @if(! $employee->user)<form method="POST" action="{{ route('admin.hr.employees.user', $employee) }}" class="card-footer d-flex gap-2">@csrf<select name="user_id" class="form-select form-select-sm">@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} · {{ $u->email }}</option>@endforeach</select><button class="btn btn-sm btn-outline-primary">{{ __('Link login') }}</button></form>
                @elseif($employee->user->is_active)@can('hr.employee.separate')<form method="POST" action="{{ route('admin.hr.employees.revoke', $employee) }}" class="card-footer">@csrf<button class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Switch off this person\'s login, roles and provider record now?') }}')">{{ __('Switch off access now') }}</button></form>@endcan @endif</div>
            @endcan

            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Contracts') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($contracts as $c)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\Illuminate\Support\Str::headline($c->contract_type)) }} · {{ $c->start_on->toDateString() }} → {{ $c->end_on?->toDateString() ?? __('open') }}</span><x-ui.status-badge :status="$c->status" /></li>@empty<li class="list-group-item text-muted">{{ __('No contract.') }}</li>@endforelse</ul>
                @can('hr.contract.manage')<form method="POST" action="{{ route('admin.hr.employees.contracts', $employee) }}" class="card-footer d-flex flex-wrap gap-2">@csrf<select name="contract_type" class="form-select form-select-sm w-auto"><option value="permanent">{{ __('Permanent') }}</option><option value="fixed_term">{{ __('Fixed term') }}</option><option value="probation">{{ __('Probation') }}</option><option value="visiting">{{ __('Visiting') }}</option></select><input type="date" name="start_on" class="form-control form-control-sm w-auto" required><input type="date" name="end_on" class="form-control form-control-sm w-auto"><button class="btn btn-sm btn-outline-primary">{{ __('New contract') }}</button></form>@endcan</div>

            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Licences and certificates') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($credentials as $c)<li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ strtoupper($c->credential_type) }} {{ $c->number }} <span class="text-muted">{{ $c->expires_on ? __('until').' '.$c->expires_on->toDateString() : '' }}</span> @if($c->verified_at)<i class="bi bi-patch-check text-success" title="{{ __('Checked with the issuing body') }}"></i>@endif</span>
                    <span class="d-flex gap-1 align-items-center"><x-ui.status-badge :status="$c->status" />@if(! $c->verified_at)@can('hr.credential.manage')<form method="POST" action="{{ route('admin.hr.credentials.verify', $c) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Checked') }}</button></form>@endcan @endif</span></li>@empty<li class="list-group-item text-muted">{{ __('None recorded.') }}</li>@endforelse</ul>
                @can('hr.credential.manage')<form method="POST" action="{{ route('admin.hr.employees.credentials', $employee) }}" class="card-footer d-flex flex-wrap gap-2">@csrf<select name="credential_type" class="form-select form-select-sm w-auto">@foreach($credentialTypes as $t)<option value="{{ $t }}">{{ strtoupper($t) }}</option>@endforeach</select><input name="number" class="form-control form-control-sm w-auto" placeholder="{{ __('Number') }}"><input type="date" name="expires_on" class="form-control form-control-sm w-auto" title="{{ __('Expires') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Add') }}</button></form>@endcan</div>

            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Documents') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($documents as $d)<li class="list-group-item">{{ __(\Illuminate\Support\Str::headline($d->doc_type)) }} · {{ $d->title }} <span class="text-muted">{{ $d->expires_on ? __('until').' '.$d->expires_on->toDateString() : '' }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No documents.') }}</li>@endforelse</ul>
                @can('hr.document.manage')<form method="POST" action="{{ route('admin.hr.employees.documents', $employee) }}" class="card-footer d-flex flex-wrap gap-2">@csrf<select name="doc_type" class="form-select form-select-sm w-auto">@foreach($documentTypes as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select><input name="title" class="form-control form-control-sm w-auto" placeholder="{{ __('Title') }}" required><input type="date" name="expires_on" class="form-control form-control-sm w-auto"><button class="btn btn-sm btn-outline-primary">{{ __('Add') }}</button></form>@endcan</div>

            @if($salary)
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Salary structure') }} <small class="text-muted">{{ __('private') }}</small></h3></div>
                @forelse($structures as $s)<div class="card-body border-top small"><div class="fw-semibold">{{ __('Version') }} {{ $s->version }} · {{ $s->effective_from->toDateString() }} → {{ $s->effective_to?->toDateString() ?? __('now') }}</div>
                    @foreach($s->lines as $l)<div class="d-flex justify-content-between"><span>{{ $l->component->name }}</span><span>{{ $l->amount !== null ? $l->amount : $l->percent.'%' }}</span></div>@endforeach</div>
                @empty<div class="card-body text-muted small">{{ __('No salary structure yet.') }}</div>@endforelse
                @can('compensation.structure.manage')<form method="POST" action="{{ route('admin.hr.employees.structures', $employee) }}" class="card-footer"> @csrf
                    <div class="row g-1">@foreach($components->where('status', 'active') as $c)<div class="col-6"><label class="form-label small mb-0">{{ $c->name }}</label><input type="number" step="0.01" min="0" name="lines[{{ $c->code }}][{{ $c->method === 'fixed' ? 'amount' : 'percent' }}]" class="form-control form-control-sm" placeholder="{{ $c->method === 'fixed' ? __('Amount') : '%' }}"></div>@endforeach</div>
                    <div class="d-flex gap-2 mt-2"><input type="date" name="effective_from" class="form-control form-control-sm w-auto" required><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why') }}"><button class="btn btn-sm btn-outline-primary">{{ __('New version') }}</button></div></form>@endcan</div>
            @endif
        </div>
    </div>
@stop
