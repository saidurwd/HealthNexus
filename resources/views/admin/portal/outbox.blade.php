@extends('layouts.adminlte')

@section('page_title', __('Messages sent to patients'))

@section('page_content')
    @include('admin.portal._flash')
    <form method="GET" class="d-flex gap-2 mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('Any outcome') }}</option>@foreach(['queued', 'deferred', 'sent', 'suppressed', 'failed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <select name="purpose" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('Any purpose') }}</option>@foreach(['transactional', 'service', 'marketing'] as $s)<option value="{{ $s }}" @selected(request('purpose') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Channel') }}</th><th>{{ __('Purpose') }}</th><th>{{ __('What') }}</th><th>{{ __('Outcome') }}</th><th>{{ __('Why') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->created_at->format('d M H:i') }}</td><td>{{ strtoupper($r->channel) }}</td><td>{{ __(\Illuminate\Support\Str::headline($r->purpose)) }}</td><td>{{ $r->template === 'otp' ? __('Sign-in code') : \Illuminate\Support\Str::limit($r->body, 60) }}</td><td><x-ui.status-badge :status="$r->status" /></td><td class="text-muted small">{{ $r->reason }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing sent yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
