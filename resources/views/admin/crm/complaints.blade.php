@extends('layouts.adminlte')

@section('page_title', __('Complaints'))

@section('page_content')
    @include('admin.crm._flash')
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" onchange="this.form.submit()">@foreach(['open' => 'Open', 'resolved' => 'Resolved', 'closed' => 'Closed', 'all' => 'All'] as $k => $t)<option value="{{ $k }}" @selected($status === $k)>{{ __($t) }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Patient') }}</th><th>{{ __('About') }}</th><th>{{ __('How serious') }}</th><th>{{ __('Answer by') }}</th><th>{{ __('With') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $c)<tr><td><a href="{{ route('admin.crm.complaints.show', $c->public_id) }}">{{ $c->number }}</a></td><td>{{ $c->patient?->first_name }} {{ $c->patient?->last_name }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->category)) }}</td><td><x-ui.status-badge :status="$c->severity" /></td><td class="{{ $c->due_at && $c->due_at->isPast() && ! in_array($c->status, ['resolved', 'closed']) ? 'text-danger' : '' }}">{{ $c->due_at?->format('d M H:i') }}</td><td>{{ $c->assignee?->name }}</td><td><x-ui.status-badge :status="$c->status" /></td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No complaints.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    @can('crm.complaint.manage')<form method="POST" action="{{ route('admin.crm.complaints.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Record a complaint') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><input name="patient_no" class="form-control" placeholder="{{ __('Patient number (if known)') }}" value="{{ old('patient_no') }}"></div>
        <div class="col-md-2"><select name="channel" class="form-select">@foreach(config('portal.crm.interaction_channels') as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="category" class="form-select">@foreach(config('portal.crm.complaint_categories') as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="severity" class="form-select"><option value="low">{{ __('Low') }}</option><option value="medium" selected>{{ __('Medium') }}</option><option value="high">{{ __('High') }}</option></select></div>
        <div class="col-12"><textarea name="description" class="form-control" rows="3" required placeholder="{{ __('What happened') }}">{{ old('description') }}</textarea></div></div><div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form>@endcan
@stop
