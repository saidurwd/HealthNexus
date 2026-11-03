@extends('layouts.adminlte')

@section('page_title', __('Campaigns'))

@section('page_content')
    @include('admin.crm._flash')
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Group') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Channel') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $c)<tr><td><a href="{{ route('admin.crm.campaigns.show', $c->public_id) }}">{{ $c->name }}</a></td><td>{{ $c->segment->name }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->purpose)) }}</td><td>{{ strtoupper($c->channel) }}</td><td><x-ui.status-badge :status="$c->status" /></td><td class="text-muted small">{{ $c->created_at->format('d M') }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No campaigns yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    @can('crm.campaign.manage')<form method="POST" action="{{ route('admin.crm.campaigns.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('New campaign') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required></div>
        <div class="col-md-3"><select name="segment_id" class="form-select" required><option value="">{{ __('Group') }}</option>@foreach($segments as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->last_count ?? '?' }})</option>@endforeach</select></div>
        <div class="col-md-2"><select name="purpose" class="form-select"><option value="service">{{ __('Service (reminders, follow-up)') }}</option><option value="marketing">{{ __('Marketing (only to those who agreed)') }}</option></select></div>
        <div class="col-md-2"><select name="channel" class="form-select"><option value="sms">SMS</option><option value="email">{{ __('Email') }}</option><option value="push">{{ __('App notification') }}</option><option value="inapp">{{ __('Portal notice') }}</option></select></div>
        <div class="col-md-1"><input type="datetime-local" name="scheduled_at" class="form-control" title="{{ __('Send not before') }}"></div>
        <div class="col-md-6"><input name="subject" class="form-control" placeholder="{{ __('Subject (email)') }}"></div>
        <div class="col-12"><textarea name="body" class="form-control" rows="3" maxlength="1000" required placeholder="{{ __('Message. {name} becomes the patient\'s name.') }}"></textarea></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save draft') }}</button></div></form>@endcan
@stop
