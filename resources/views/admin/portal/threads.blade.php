@extends('layouts.adminlte')

@section('page_title', __('Patient messages'))

@section('page_content')
    @include('admin.portal._flash')
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()">@foreach(['open', 'answered', 'closed', 'all'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Subject') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Last message') }}</th><th>{{ __('Reply by') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $t)<tr class="{{ $t->urgent ? 'table-danger' : '' }}"><td>{{ $t->patient->first_name }} {{ $t->patient->last_name }} <span class="text-muted">{{ $t->patient->enterprise_patient_no }}</span></td><td><a href="{{ route('admin.portal.threads.show', $t->public_id) }}">{{ $t->subject }}</a> @if($t->urgent)<span class="badge text-bg-danger"><i class="bi bi-lightning-charge-fill"></i> {{ __('Urgent') }}</span>@endif</td><td>{{ __(\Illuminate\Support\Str::headline($t->category)) }}</td><td>{{ $t->last_message_at?->diffForHumans() }}</td><td class="{{ $t->reply_due_at && $t->reply_due_at->isPast() ? 'text-danger' : '' }}">{{ $t->reply_due_at?->format('d M H:i') }}</td><td><x-ui.status-badge :status="$t->status" /></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No messages.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
