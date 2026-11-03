@extends('layouts.adminlte')

@section('page_title', $r->name)

@section('page_content')
    @include('admin.analytics._flash')
    @include('admin.analytics._range', ['from' => $from, 'to' => $to])
    @php($cols = collect($result['columns']))
    <div class="mb-3 d-flex gap-2">@if(auth()->user()->can('analytics.report.export'))<a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-filetype-csv"></i> CSV</a><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"><i class="bi bi-filetype-pdf"></i> PDF</a>@endif
        @can('analytics.report.manage')<form method="POST" action="{{ route('admin.analytics.reports.delete', $r->public_id) }}" onsubmit="return confirm('{{ __('Delete this report?') }}')">@csrf<button class="btn btn-outline-danger">{{ __('Delete') }}</button></form>@endcan</div>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>@foreach($cols as $c)<th class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ __($c['label']) }}</th>@endforeach</tr></thead><tbody>
        @forelse($result['rows'] as $row)<tr>@foreach($cols as $c)<td class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ $c['kind'] === 'measure' ? \App\Support\AnalyticsFormat::value($row[$c['key']] ?? null, $c['unit']) : ($row[$c['key']] ?? '—') }}</td>@endforeach</tr>@empty<tr><td colspan="{{ $cols->count() }}" class="text-muted">{{ __('Nothing for this period.') }}</td></tr>@endforelse</tbody></table></div></div>
    @can('analytics.schedule.manage')<div class="card"><div class="card-header"><h3 class="card-title">{{ __('Send this report by e-mail') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($schedules as $s)<li class="list-group-item">{{ __(\Illuminate\Support\Str::headline($s->frequency)) }} {{ $s->at_time }} · {{ strtoupper($s->format) }} · {{ implode(', ', $s->recipients) }} · {{ $s->active ? __('on') : __('off') }}</li>@empty<li class="list-group-item text-muted">{{ __('Not scheduled.') }}</li>@endforelse</ul>
        <form method="POST" action="{{ route('admin.analytics.reports.schedule', $r->public_id) }}" class="card-body row g-2">@csrf
            <div class="col-md-2"><label class="form-label" for="frequency">{{ __('How often') }}</label><select id="frequency" name="frequency" class="form-select"><option value="daily">{{ __('Daily') }}</option><option value="weekly">{{ __('Weekly') }}</option><option value="monthly">{{ __('Monthly') }}</option></select></div>
            <div class="col-md-2"><label class="form-label" for="day">{{ __('Day (weekday 1-7, or day of month 1-28)') }}</label><input id="day" type="number" name="day" class="form-control" min="1" max="28"></div>
            <div class="col-md-2"><label class="form-label" for="at_time">{{ __('At') }}</label><input id="at_time" name="at_time" class="form-control" value="07:00" pattern="\d{2}:\d{2}"></div>
            <div class="col-md-2"><label class="form-label" for="format">{{ __('As') }}</label><select id="format" name="format" class="form-select"><option value="csv">CSV</option><option value="pdf">PDF</option></select></div>
            <div class="col-md-4"><label class="form-label" for="recipients">{{ __('To (e-mail addresses of our staff, separated by commas)') }}</label><input id="recipients" name="recipients" class="form-control" required></div>
            <div class="col-12"><button class="btn btn-primary">{{ __('Schedule') }}</button> <span class="text-muted small">{{ __('A daily report covers yesterday, a weekly one the last seven days, a monthly one the month just ended.') }}</span></div></form></div>@endcan
@stop
