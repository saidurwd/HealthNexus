@extends('layouts.adminlte')

@section('page_title', __('Messages between systems'))

@section('page_content')
    @include('admin.interop._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <select name="status" class="form-select w-auto" aria-label="{{ __('Status') }}" onchange="this.form.submit()"><option value="">{{ __('Any status') }}</option>@foreach(['queued', 'sending', 'retrying', 'processed', 'dead', 'dismissed', 'received', 'rejected'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <select name="direction" class="form-select w-auto" aria-label="{{ __('Direction') }}" onchange="this.form.submit()"><option value="">{{ __('Both ways') }}</option><option value="out" @selected(request('direction') === 'out')>{{ __('Sent') }}</option><option value="in" @selected(request('direction') === 'in')>{{ __('Received') }}</option></select>
        <select name="channel" class="form-select w-auto" aria-label="{{ __('Channel') }}" onchange="this.form.submit()"><option value="">{{ __('Any channel') }}</option>@foreach(['fhir', 'hl7', 'dicom', 'webhook', 'national'] as $c)<option value="{{ $c }}" @selected(request('channel') === $c)>{{ strtoupper($c) }}</option>@endforeach</select>
        <select name="partner" class="form-select w-auto" aria-label="{{ __('Partner') }}" onchange="this.form.submit()"><option value="">{{ __('Any partner') }}</option>@foreach($partners as $p)<option value="{{ $p->code }}" @selected(request('partner') === $p->code)>{{ $p->name }}</option>@endforeach</select>
        <input name="q" value="{{ request('q') }}" class="form-control w-auto" placeholder="{{ __('Type, or message id') }}" aria-label="{{ __('Search') }}"><button class="btn btn-outline-primary">{{ __('Search') }}</button></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Partner') }}</th><th>{{ __('Way') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Tries') }}</th><th>{{ __('Problem') }}</th></tr></thead>
        <tbody>@forelse($rows as $m)<tr><td><a href="{{ route('admin.interop.messages.show', $m->public_id) }}">{{ $m->created_at->format('d M H:i:s') }}</a></td><td>{{ $m->partner?->name }}</td><td>{{ $m->direction === 'out' ? '↗ '.__('sent') : '↙ '.__('received') }} · {{ strtoupper($m->channel) }}</td><td>{{ $m->message_type }}</td><td><x-ui.status-badge :status="$m->status" /></td><td class="text-end">{{ $m->attempts }}</td><td class="small text-muted">{{ \Illuminate\Support\Str::limit((string) $m->error, 70) }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No messages.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
