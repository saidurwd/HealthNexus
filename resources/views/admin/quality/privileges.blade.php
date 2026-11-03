@extends('layouts.adminlte')

@section('page_title', __('Clinical privileges'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['granted' => 'In force', 'requested' => 'Waiting', 'expired' => 'Expired', 'suspended' => 'Suspended', 'all' => 'All'] as $k => $l)<a class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.quality.privileges', ['status' => $k]) }}">{{ __($l) }}</a>@endforeach</div>
    <p class="small text-muted">{{ __('A privilege says which procedures a clinician may perform here. It needs a valid licence, a decision by a second person, and it ends on a date.') }}</p>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Clinician') }}</th><th>{{ __('Privilege') }}</th><th>{{ __('Status') }}</th><th>{{ __('Until') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $p)<tr><td>{{ $names[$p->employee_id] ?? '—' }}</td><td>{{ $p->item->name }}</td><td><x-ui.status-badge :status="$p->status" /></td><td>{{ $p->expires_on?->format('d M Y') ?? '—' }}</td>
            <td style="min-width: 260px">
                @if($p->status === 'requested' && $can['decide'])<form method="POST" action="{{ route('admin.quality.privileges.decide', $p->public_id) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"><button name="decision" value="grant" class="btn btn-sm btn-success">{{ __('Grant') }}</button><button name="decision" value="decline" class="btn btn-sm btn-outline-secondary">{{ __('Decline') }}</button></form>
                @elseif($p->status === 'granted' && $can['manage'])<form method="POST" action="{{ route('admin.quality.privileges.withdraw', $p->public_id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Reason') }}" required><button class="btn btn-sm btn-outline-danger">{{ __('Withdraw') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('Nothing here.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    @if($can['request'])<form method="POST" action="{{ route('admin.quality.privileges.request') }}" class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Ask for a privilege') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><select name="employee_id" class="form-select form-select-sm">@foreach($employees as $e)<option value="{{ $e->id }}">{{ trim($e->first_name.' '.$e->last_name) }} ({{ $e->employee_no }})</option>@endforeach</select></div><div class="col-md-4"><select name="catalog_id" class="form-select form-select-sm">@foreach($catalog as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"></div><div class="col-md-1"><button class="btn btn-sm btn-primary w-100">{{ __('Ask') }}</button></div></div></form>@endif
    @if($can['manage'])<form method="POST" action="{{ route('admin.quality.privileges.catalog') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Privilege list') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-12 small text-muted">@foreach($catalog as $c)<span class="badge text-bg-light border me-1">{{ $c->code }} · {{ $c->name }}@if($c->requires_credential) · {{ strtoupper($c->requires_credential) }}@endif</span>@endforeach</div>
        <div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="{{ __('Code') }}" required></div><div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="{{ __('Name') }}" required></div><div class="col-md-2"><input name="requires_credential" class="form-control form-control-sm" placeholder="{{ __('Licence needed (bmdc)') }}"></div>
        <div class="col-md-2"><input type="number" name="validity_months" class="form-control form-control-sm" placeholder="{{ __('Months') }}" min="1"></div><div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">{{ __('Save') }}</button></div></div></form>@endif
@stop
