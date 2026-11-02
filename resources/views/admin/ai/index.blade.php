@extends('layouts.adminlte')

@section('page_title', __('AI assistant'))

@section('page_content')
    @include('admin.ai._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="alert alert-info small">{{ __('The assistant reads records back, drafts and forecasts. It never diagnoses, prescribes, orders, discharges, bills or writes into a record. Everything it produces is a draft with its sources, and a clinician decides what to use.') }}</div>
    @unless($on)<div class="alert alert-danger"><strong>{{ __('AI is switched off for the whole hospital.') }}</strong></div>@endunless
    @if($can['use'] && $on)
    <div class="row g-2 mb-3">
        @foreach([['admin.ai.summary', 'Read a record back', 'bi-person-lines-fill', 'summary'], ['admin.ai.notes', 'Draft a visit note', 'bi-journal-text', 'notes'], ['admin.ai.ask', 'Ask about hospital policy', 'bi-book', 'ask'], ['admin.ai.figures', 'Ask about the figures', 'bi-bar-chart-line', 'figures'], ['admin.ai.forecast', 'Forecast an item\'s use', 'bi-graph-up-arrow', 'forecast'], ['admin.ai.claim', 'Check a claim', 'bi-shield-check', 'insurance_claim_check'], ['admin.ai.worklist', 'Suggest a reading order', 'bi-list-ol', 'radiology_worklist'], ['admin.ai.medicines', 'Medicines for review', 'bi-capsule', 'medication_review'], ['admin.ai.score', 'Early-warning screen', 'bi-activity', 'risk_scoring'], ['admin.ai.outputs', 'My drafts', 'bi-inbox', 'outputs']] as [$route, $label, $icon, $code])
            @continue(in_array($code, ['insurance_claim_check', 'radiology_worklist', 'medication_review', 'risk_scoring'], true) && ! $useCases->where('code', $code)->where('status', 'enabled')->count())
            <div class="col-6 col-md-4 col-xl-2"><a class="btn btn-outline-primary w-100 text-start" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i> {{ __($label) }}</a></div>
        @endforeach
    </div>
    @endif
    @if(! $hasModel && $can['start'])<form method="POST" action="{{ route('admin.ai.start') }}" class="alert alert-warning">@csrf <strong>{{ __('Nothing is set up yet.') }}</strong> {{ __('Start with the local engine: it uses no outside service and no model, it only re-states facts. It is evaluated first, and only then are the low-risk functions switched on.') }} <button class="btn btn-sm btn-warning ms-2">{{ __('Set up the local engine') }}</button></form>@endif
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('What AI is used for here') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Function') }}</th><th>{{ __('Risk') }}</th><th>{{ __('Status') }}</th><th>{{ __('Model') }}</th><th class="text-end">{{ __('Drafts (30 days)') }}</th><th class="text-end">{{ __('Accepted') }}</th></tr></thead><tbody>
        @foreach($useCases as $u)@php($m = $metrics[$u->code] ?? null)<tr class="{{ $u->implemented ? '' : 'text-muted' }}"><td>{{ __($u->name) }} @unless($u->implemented)<span class="badge text-bg-light border">{{ __('planned') }}</span>@endunless</td><td>{{ $H($u->risk_class) }}</td><td><x-ui.status-badge :status="$u->status === 'enabled' ? 'active' : ($u->status === 'paused' ? 'suspended' : ($u->status === 'planned' ? 'pending' : 'retired'))" :label="$H($u->status)" /></td>
            <td class="small">{{ $u->model ? $u->model->name.' v'.$u->model->version : '—' }}</td><td class="text-end">{{ $m['outputs'] ?? '—' }}</td><td class="text-end">{{ isset($m) && $m['acceptance'] !== null ? $m['acceptance'].'%' : '—' }}</td></tr>@endforeach</tbody></table></div></div>
    @if($can['kill'])<form method="POST" action="{{ route('admin.ai.killswitch') }}" class="card card-body mt-3 border-danger row g-2">@csrf<div class="small text-muted">{{ __('The master switch stops every AI function at once, for the whole hospital.') }}</div>
        <div class="col-md-7"><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why? (needed to switch off)') }}"></div><div class="col-md-5 d-flex gap-2"><button name="state" value="off" class="btn btn-sm btn-danger">{{ __('Switch AI off') }}</button><button name="state" value="on" class="btn btn-sm btn-outline-secondary">{{ __('Switch AI on') }}</button></div></form>@endif
@stop
