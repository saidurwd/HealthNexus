@extends('layouts.adminlte')

@section('page_title', __('Welfare funds'))

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('A fund\'s balance is the sum of what was received less what was given. Nothing is edited: a mistake is put right with a reversal that says why.') }}</p>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Fund') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Balance') }}</th><th class="text-end">{{ __('Set aside for grants') }}</th><th class="text-end">{{ __('Free to give') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td><a href="{{ route('admin.welfare.funds.show', $r['fund']->public_id) }}">{{ $r['fund']->name }}</a></td><td>{{ $H($r['fund']->kind) }}</td><td><x-ui.status-badge :status="$r['fund']->status === 'active' ? 'active' : 'closed'" :label="$H($r['fund']->status)" /></td><td class="text-end">{{ number_format((float) $r['balance'], 2) }}</td><td class="text-end">{{ number_format((float) $r['committed'], 2) }}</td><td class="text-end fw-semibold">{{ number_format((float) $r['available'], 2) }}</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('No funds yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.welfare.funds.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Add a fund') }}</h3></div><div class="card-body row g-2">
        <div class="col-md-4"><label class="form-label">{{ __('Name') }}</label><input name="name" class="form-control form-control-sm" required maxlength="120"></div>
        <div class="col-md-2"><label class="form-label">{{ __('Kind') }}</label><select name="kind" class="form-select form-select-sm">@foreach($kinds as $k)<option value="{{ $k }}">{{ $H($k) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Most for one patient') }}</label><input type="number" step="0.01" min="0" name="per_grant_cap" class="form-control form-control-sm"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Warn when free money falls below') }}</label><input type="number" step="0.01" min="0" name="low_balance_alert" class="form-control form-control-sm"></div>
        <div class="col-12"><label class="form-label">{{ __('What it is for') }}</label><input name="description" class="form-control form-control-sm" maxlength="300"></div>
        <div class="col-12"><button class="btn btn-primary btn-sm">{{ __('Add the fund') }}</button></div></div></form>@endif
@stop
