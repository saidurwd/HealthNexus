@extends('layouts.adminlte')

@section('page_title', __('Payers and plans'))

@section('page_content')
    @include('admin.insurance._flash')
    @can('insurance.payer.manage')
    <form method="POST" action="{{ route('admin.insurance.payers.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Add a payer') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><input name="code" class="form-control" placeholder="{{ __('Code') }}" required maxlength="30"></div>
            <div class="col-md-4"><input name="name" class="form-control" placeholder="{{ __('Name') }}" required maxlength="150"></div>
            <div class="col-md-2"><select name="payer_type" class="form-select">@foreach($types as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="submission_method" class="form-select">@foreach($methods as $m)<option value="{{ $m }}">{{ __(\Illuminate\Support\Str::headline($m)) }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-danger w-100">{{ __('Add') }}</button></div>
        </div></form>
    @endcan
    @forelse($payers as $payer)
        <div class="card"><div class="card-header"><h3 class="card-title">{{ $payer->name }} <small class="text-muted">{{ $payer->code }} · {{ __(\Illuminate\Support\Str::headline($payer->payer_type)) }} · {{ __(\Illuminate\Support\Str::headline($payer->submission_method)) }}</small></h3></div>
            <ul class="list-group list-group-flush small">@forelse($payer->plans as $plan)<li class="list-group-item">{{ $plan->name }} <span class="text-muted">· {{ __('co-pay') }} {{ $plan->copay_percent }}% · {{ __('deductible') }} {{ $plan->deductible_amount }} · {{ __('yearly limit') }} {{ $plan->annual_limit ?? '—' }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No plans yet.') }}</li>@endforelse</ul>
            @can('insurance.plan.manage')<form method="POST" action="{{ route('admin.insurance.plans.store', $payer) }}" class="card-footer d-flex flex-wrap gap-2">@csrf
                <input name="code" class="form-control form-control-sm w-auto" placeholder="{{ __('Code') }}" required><input name="name" class="form-control form-control-sm w-auto" placeholder="{{ __('Plan name') }}" required>
                <input type="number" step="0.01" name="copay_percent" class="form-control form-control-sm" style="width:100px" placeholder="{{ __('Co-pay %') }}" value="0" required><input type="number" step="0.01" name="deductible_amount" class="form-control form-control-sm" style="width:110px" placeholder="{{ __('Deductible') }}">
                <input type="number" step="0.01" name="annual_limit" class="form-control form-control-sm" style="width:120px" placeholder="{{ __('Yearly limit') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Add plan') }}</button></form>@endcan
        </div>
    @empty<div class="alert alert-info">{{ __('No payers yet.') }}</div>@endforelse
@stop
