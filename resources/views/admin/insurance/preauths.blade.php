@extends('layouts.adminlte')

@section('page_title', __('Pre-authorizations'))

@section('page_content')
    @include('admin.insurance._flash')
    @can('insurance.preauth.request')
    <form method="POST" action="{{ route('admin.insurance.preauths.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Request a pre-authorization') }}</h3></div>
        <div class="card-body d-flex flex-wrap gap-2"><input name="policy" class="form-control w-auto" placeholder="{{ __('Policy ID (from the policy list)') }}" required maxlength="26"><input name="service_description" class="form-control w-auto flex-grow-1" placeholder="{{ __('What is planned') }}" required maxlength="300"><input type="number" step="0.01" name="requested_amount" class="form-control w-auto" placeholder="{{ __('Amount') }}" required><button class="btn btn-danger">{{ __('Request') }}</button></div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>{{ __('Request') }}</th><th>{{ __('Planned') }}</th><th class="text-end">{{ __('Asked') }}</th><th class="text-end">{{ __('Authorized') }}</th><th class="text-end">{{ __('Used') }}</th><th>{{ __('Status') }}</th><th>{{ __('Valid until') }}</th><th></th></tr></thead><tbody>
        @forelse($preauths as $p)
            <tr><td>{{ $p->request_number }}</td><td>{{ $p->service_description }}</td><td class="text-end">{{ $p->requested_amount }}</td><td class="text-end">{{ $p->authorized_amount }}</td><td class="text-end">{{ $p->consumed_amount }}</td><td><x-ui.status-badge :status="$p->status" /></td><td>{{ $p->valid_to?->format('d M Y') ?? '—' }}</td>
                <td>@if($p->status === 'requested')@can('insurance.preauth.decide')<form method="POST" action="{{ route('admin.insurance.preauths.decide', $p) }}" class="d-flex gap-1">@csrf<select name="status" class="form-select form-select-sm"><option value="approved">{{ __('Approved') }}</option><option value="partial">{{ __('Partly approved') }}</option><option value="rejected">{{ __('Rejected') }}</option></select><input type="number" step="0.01" name="authorized_amount" class="form-control form-control-sm" style="width:100px" value="{{ $p->requested_amount }}"><input name="payer_reference" class="form-control form-control-sm" style="width:110px" placeholder="{{ __('Payer ref') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button></form>@endcan @endif</td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('No pre-authorizations.') }}</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $preauths->links() }}</div></div>
@stop
