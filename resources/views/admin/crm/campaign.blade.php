@extends('layouts.adminlte')

@section('page_title', $c->name)

@section('page_content')
    @include('admin.crm._flash')
    <div class="card"><div class="card-body d-flex flex-wrap justify-content-between gap-2"><div>{{ __('Group') }}: <strong>{{ $c->segment->name }}</strong> · {{ __(\Illuminate\Support\Str::headline($c->purpose)) }} · {{ strtoupper($c->channel) }}<div class="mt-2 p-2 bg-light border rounded" style="white-space: pre-wrap">{{ $c->body }}</div></div><div class="text-end"><x-ui.status-badge :status="$c->status" />@if($c->approved_at)<div class="small text-muted">{{ __('approved') }} {{ $c->approved_at->format('d M H:i') }}</div>@endif</div></div>
        <div class="card-footer d-flex gap-2"><form method="POST" action="{{ route('admin.crm.campaigns.action', $c->public_id) }}" class="d-flex gap-2">@csrf
            @if($c->status === 'draft')@can('crm.campaign.manage')<button name="action" value="submit" class="btn btn-sm btn-outline-primary">{{ __('Send for approval') }}</button>@endcan @endif
            @if($c->status === 'pending')@can('crm.campaign.approve')<button name="action" value="approve" class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button>@endcan @endif
            @if($c->status === 'approved')@can('crm.campaign.approve')<button name="action" value="run" class="btn btn-sm btn-success" onclick="return confirm('{{ __('Send this now?') }}')">{{ __('Send now') }}</button>@endcan @endif
            @if(in_array($c->status, ['draft', 'pending', 'approved']))@can('crm.campaign.manage')<button name="action" value="cancel" class="btn btn-sm btn-outline-danger">{{ __('Cancel') }}</button>@endcan @endif</form></div></div>
    @if($c->stats)<div class="row g-3 mb-3">@foreach($c->stats as $k => $n)<div class="col-6 col-md-2"><div class="card"><div class="card-body"><x-ui.status-badge :status="$k" /><div class="fs-4">{{ $n }}</div></div></div></div>@endforeach</div>@endif
    @if($reasons)<div class="card"><div class="card-header"><h3 class="card-title">{{ __('Why some patients were not messaged') }}</h3></div><ul class="list-group list-group-flush small">@foreach($reasons as $r => $n)<li class="list-group-item d-flex justify-content-between"><span>{{ __($r) }}</span><strong>{{ $n }}</strong></li>@endforeach</ul></div>@endif
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Outcome') }}</th><th>{{ __('Why') }}</th></tr></thead><tbody>@forelse($recipients as $r)<tr><td>{{ $r->patient->first_name }} {{ $r->patient->last_name }} <span class="text-muted">{{ $r->patient->enterprise_patient_no }}</span></td><td><x-ui.status-badge :status="$r->status" /></td><td class="small text-muted">{{ $r->reason }}</td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('Not sent yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $recipients->links() }}</div></div>
@stop
