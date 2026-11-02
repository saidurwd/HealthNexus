@extends('layouts.adminlte')

@section('page_title', $fund->name)

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="row g-2 mb-3">
        <div class="col-md-4"><div class="card card-body py-2"><div class="small text-muted">{{ __('Balance') }}</div><div class="fs-5 fw-semibold">৳ {{ number_format((float) $balance, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card card-body py-2"><div class="small text-muted">{{ __('Set aside for grants') }}</div><div class="fs-5 fw-semibold">৳ {{ number_format((float) $committed, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card card-body py-2"><div class="small text-muted">{{ __('Free to give') }}</div><div class="fs-5 fw-semibold">৳ {{ number_format((float) $available, 2) }}</div></div></div>
    </div>
    <p class="small text-muted">{{ $H($fund->kind) }} · <x-ui.status-badge :status="$fund->status === 'active' ? 'active' : 'closed'" :label="$H($fund->status)" />@if($fund->per_grant_cap !== null) · {{ __('Most for one patient') }}: ৳ {{ number_format((float) $fund->per_grant_cap, 2) }}@endif @if($fund->description)<br>{{ $fund->description }}@endif</p>
    @if($canRecord && $fund->status === 'active')<form method="POST" action="{{ route('admin.welfare.funds.donate', $fund->public_id) }}" class="card mb-3">@csrf<div class="card-header"><h3 class="card-title">{{ __('Record money received') }}</h3></div><div class="card-body row g-2">
        <div class="col-md-3"><label class="form-label">{{ __('Amount') }}</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('Donor on file') }}</label><select name="donor_id" class="form-select form-select-sm"><option value="">—</option>@foreach($donors as $d)<option value="{{ $d->id }}">{{ $d->anonymous ? __('Anonymous') : $d->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Or a new donor') }}</label><input name="donor_name" class="form-control form-control-sm" maxlength="120"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Phone') }}</label><input name="donor_phone" class="form-control form-control-sm" maxlength="30"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Cash, cheque or transfer reference') }}</label><input name="reference" class="form-control form-control-sm" maxlength="80"></div>
        <div class="col-md-6"><label class="form-label">{{ __('Note') }}</label><input name="note" class="form-control form-control-sm" maxlength="300"></div>
        <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="anonymous" value="1" id="anon"><label class="form-check-label" for="anon">{{ __('Do not show the donor\'s name') }}</label></div></div>
        <div class="col-12"><button class="btn btn-primary btn-sm">{{ __('Record and give a receipt') }}</button></div></div></form>@endif
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Ledger') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('What') }}</th><th>{{ __('Receipt') }}</th><th>{{ __('Donor') }}</th><th>{{ __('Reference') }}</th><th class="text-end">{{ __('Amount') }}</th><th></th></tr></thead><tbody>
        @forelse($entries as $e)<tr><td>{{ $e->occurred_at?->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ $H($e->entry_type) }}</td><td>{{ $e->receipt_no }}</td><td>{{ $e->donor ? ($e->donor->anonymous ? __('Anonymous') : $e->donor->name) : '' }}</td><td>{{ $e->reference }} {{ $e->note }}</td><td class="text-end {{ (float) $e->amount < 0 ? 'text-danger' : '' }}">{{ number_format((float) $e->amount, 2) }}</td>
            <td>@if($canManage && $e->entry_type === 'donation' && ! in_array($e->public_id, $reversed, true))<form method="POST" action="{{ route('admin.welfare.entries.reverse', $e->public_id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why?') }}" required maxlength="300"><button class="btn btn-sm btn-outline-danger">{{ __('Reverse') }}</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="text-muted">{{ __('Nothing yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @if($canManage && $fund->status === 'active')<form method="POST" action="{{ route('admin.welfare.funds.close', $fund->public_id) }}" class="card">@csrf<div class="card-body d-flex gap-2"><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why close this fund?') }}" required maxlength="300"><button class="btn btn-sm btn-outline-secondary">{{ __('Close the fund') }}</button></div></form>@endif
@stop
