@extends('layouts.adminlte')

@section('page_title', __('Who looked at what'))

@section('page_content')
    @include('admin.portal._flash')
    <form method="GET" class="d-flex gap-2 mb-3"><select name="action" class="form-select w-auto" onchange="this.form.submit()"><option value="">{{ __('Any action') }}</option>@foreach(['view_result', 'view_invoice', 'view_record', 'download_document', 'share_link', 'consent_grant', 'consent_withdraw'] as $s)<option value="{{ $s }}" @selected(request('action') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <div class="form-check align-self-center"><input type="checkbox" class="form-check-input" name="proxy" value="1" id="px" @checked(request()->boolean('proxy')) onchange="this.form.submit()"><label for="px" class="form-check-label">{{ __('Only relatives acting for someone') }}</label></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Signed-in person') }}</th><th>{{ __('Record of') }}</th><th>{{ __('Action') }}</th><th>{{ __('Through a relative?') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->created_at->format('d M H:i') }}</td><td>{{ \App\Services\Portal\PhoneNumber::mask($r->account?->phone) }}</td><td>{{ $r->patient?->first_name }} {{ $r->patient?->last_name }} <span class="text-muted">{{ $r->patient?->enterprise_patient_no }}</span></td><td>{{ __(\Illuminate\Support\Str::headline($r->action)) }}</td><td>{{ $r->as_proxy ? __('Yes') : '' }}</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('Nothing yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Document links') }}</h3></div><ul class="list-group list-group-flush small">@forelse($links as $l)<li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $l->title }} · {{ __('expires') }} {{ $l->expires_at->format('d M') }} · {{ $l->downloads }} {{ __('opened') }} @if($l->revoked_at)<span class="text-muted">({{ __('revoked') }})</span>@endif</span>
        @can('portal.account.manage')@if(! $l->revoked_at && $l->expires_at->isFuture())<form method="POST" action="{{ route('admin.portal.links.revoke', $l->public_id) }}">@csrf<button class="btn btn-sm btn-outline-danger">{{ __('Stop this link') }}</button></form>@endif @endcan</li>@empty<li class="list-group-item text-muted">{{ __('None yet.') }}</li>@endforelse</ul></div>
@stop
