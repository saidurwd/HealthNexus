@extends('layouts.adminlte')

@section('page_title', __('People and payroll'))

@section('page_content')
    @include('admin.hr._flash')
    @php($tiles = [
        [__('People working here'), $d['headcount'], false, route('admin.hr.employees')],
        [__('Being onboarded'), $d['onboarding'], false, route('admin.hr.employees', ['status' => 'onboarding'])],
        [__('Joined in the last 30 days'), $d['joined_30'], false, null],
        [__('Left in the last 30 days'), $d['left_30'], false, null],
        [__('On leave today'), $d['on_leave_today'], false, route('admin.hr.attendance', ['status' => 'leave'])],
        [__('Absent today'), $d['absent_today'], $d['absent_today'] > 0, route('admin.hr.attendance', ['status' => 'absent'])],
        [__('Leave waiting for a decision'), $d['leave_to_approve'], $d['leave_to_approve'] > 0, route('admin.hr.leave')],
        [__('Attendance corrections waiting'), $d['corrections_to_decide'], $d['corrections_to_decide'] > 0, route('admin.hr.corrections')],
        [__('Overtime waiting for approval'), $d['overtime_to_approve'], $d['overtime_to_approve'] > 0, route('admin.hr.overtime')],
        [__('Licences expired (people still working)'), $d['credentials_expired'], $d['credentials_expired'] > 0, route('admin.hr.reports', ['report' => 'credentials'])],
        [__('Licences ending in 30 days'), $d['credentials_expiring_30'], $d['credentials_expiring_30'] > 0, route('admin.hr.reports', ['report' => 'credentials'])],
        [__('Contracts ending in 30 days'), $d['contracts_ending_30'], $d['contracts_ending_30'] > 0, null],
        [__('Device numbers nobody is matched to'), $d['unmapped_punches'], $d['unmapped_punches'] > 0, route('admin.hr.attendance')],
        [__('Provider links to confirm'), $d['provider_links_pending'], $d['provider_links_pending'] > 0, route('admin.hr.provider-links')],
        [__('Open vacancies'), $d['open_vacancies'], false, route('admin.hr.vacancies')],
    ])
    <div class="row g-3 mb-3">
        @foreach($tiles as [$label, $value, $warn, $url])
            <div class="col-6 col-md-4 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div>
                <div class="fs-4 fw-semibold {{ $warn ? 'text-danger' : '' }}">@if($url)<a href="{{ $url }}" class="{{ $warn ? 'text-danger' : '' }}">{{ $value }}</a>@else{{ $value }}@endif</div></div></div></div>
        @endforeach
    </div>
    <div class="row g-3">
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('People by department') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($byDepartment as $r)<li class="list-group-item d-flex justify-content-between"><span>{{ $r->department }}</span><strong>{{ $r->headcount }}</strong></li>@empty<li class="list-group-item text-muted">{{ __('Nobody yet.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Contracts ending soon') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($expiringContracts as $c)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('admin.hr.employees.show', $c->employee) }}">{{ $c->employee->fullName() }}</a><span>{{ $c->end_on->toDateString() }}</span></li>@empty<li class="list-group-item text-muted">{{ __('None in the next 30 days.') }}</li>@endforelse</ul></div>
            @can('payroll.run.view')<div class="card"><div class="card-body small">{{ __('Last payroll') }}: @if($d['last_payroll']){{ $d['last_payroll']['number'] }} · <x-ui.status-badge :status="$d['last_payroll']['status']" /> · {{ $d['last_payroll']['employees'] }} {{ __('people') }}@else{{ __('none yet') }}@endif</div></div>@endcan</div>
    </div>
@stop
