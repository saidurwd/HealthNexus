@extends('layouts.adminlte')

@section('page_title', __('Scheduled reports'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Report') }}</th><th>{{ __('When') }}</th><th>{{ __('Format') }}</th><th>{{ __('To') }}</th><th>{{ __('Next') }}</th><th>{{ __('Last') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $s)<tr><td><a href="{{ route('admin.analytics.reports.show', $s->report->public_id) }}">{{ $s->report->name }}</a></td><td>{{ __(\Illuminate\Support\Str::headline($s->frequency)) }} {{ $s->at_time }}</td><td>{{ strtoupper($s->format) }}</td><td class="small">{{ implode(', ', $s->recipients) }}</td><td>{{ $s->active ? $s->next_run_at?->setTimezone('Asia/Dhaka')->format('d M H:i') : '—' }}</td><td>{{ $s->last_status ? __(\Illuminate\Support\Str::headline($s->last_status)) : '—' }}</td>
            <td><form method="POST" action="{{ route('admin.analytics.schedules.toggle', $s->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $s->active ? __('Switch off') : __('Switch on') }}</button></form></td></tr>@empty<tr><td colspan="7" class="text-muted">{{ __('Nothing is scheduled.') }}</td></tr>@endforelse</tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('What was sent') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Time') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('People') }}</th><th class="text-end">{{ __('Rows') }}</th><th>{{ __('Fingerprint of the file') }}</th><th>{{ __('Problem') }}</th></tr></thead><tbody>
        @foreach($deliveries as $d)<tr><td>{{ $d->run_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td><x-ui.status-badge :status="$d->status" /></td><td class="text-end">{{ $d->recipients }}</td><td class="text-end">{{ $d->rows }}</td><td class="small text-muted">{{ substr((string) $d->checksum, 0, 12) }}</td><td class="small">{{ $d->error }}</td></tr>@endforeach</tbody></table></div></div>
@stop
