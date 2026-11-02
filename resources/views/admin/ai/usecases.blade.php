@extends('layouts.adminlte')

@section('page_title', __('AI functions'))

@section('page_content')
    @include('admin.ai._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('Each function has an owner, an intended use, a risk class, and a model that passed its evaluation. A function that is only planned cannot be switched on. A function above low risk needs a second person to switch it on. Anyone with the kill-switch right can pause one at once.') }}</p>
    @foreach($useCases as $u)
    <div class="card mb-2 {{ $u->implemented ? '' : 'bg-light' }}"><div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center"><strong>{{ __($u->name) }}</strong><span class="badge text-bg-secondary">{{ __('Risk') }}: {{ $H($u->risk_class) }}</span><span class="badge text-bg-light border">{{ $u->data_level }}</span><x-ui.status-badge :status="$u->status === 'enabled' ? 'active' : ($u->status === 'paused' ? 'suspended' : ($u->status === 'planned' ? 'pending' : 'retired'))" :label="$H($u->status)" /> @unless($u->implemented)<span class="badge text-bg-light border">{{ __('planned — not built') }}</span>@endunless
            <span class="small text-muted ms-auto">{{ __('Owner') }}: {{ $u->owner?->name ?? '—' }} · {{ $u->model ? $u->model->name.' v'.$u->model->version : __('no model') }}</span></div>
        <div class="small text-muted">{{ $u->intended_use }}</div>@if($u->paused_reason)<div class="small text-danger">{{ $u->paused_reason }}</div>@endif
        @if($u->implemented && $u->status !== 'retired')
        <div class="d-flex flex-wrap gap-2 mt-1">
            @if($can['manage'])<form method="POST" action="{{ route('admin.ai.usecases.setup', $u->public_id) }}" class="d-flex gap-1">@csrf<select name="owner_id" class="form-select form-select-sm"><option value="">{{ __('Owner') }}</option>@foreach($staff as $s)<option value="{{ $s->id }}" @selected($u->owner_id === $s->id)>{{ $s->name }}</option>@endforeach</select>
                <select name="model_id" class="form-select form-select-sm"><option value="">{{ __('Model') }}</option>@foreach($providers as $p)@foreach($p->models->where('status', 'approved') as $m)<option value="{{ $m->id }}" @selected($u->model_id === $m->id)>{{ $p->name }} · {{ $m->name }} v{{ $m->version }}</option>@endforeach @endforeach</select>
                <input type="hidden" name="provider_id" value="{{ $u->provider_id }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Save') }}</button></form>@endif
            @if($can['approve'] && $u->status !== 'enabled')<form method="POST" action="{{ route('admin.ai.usecases.enable', $u->public_id) }}">@csrf<button class="btn btn-sm btn-success">{{ __('Switch on') }}</button></form>@endif
            @if($can['kill'] && $u->status === 'enabled')<form method="POST" action="{{ route('admin.ai.usecases.pause', $u->public_id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why pause?') }}" required><button class="btn btn-sm btn-danger">{{ __('Pause now') }}</button></form>@endif
            @if($can['manage'])<form method="POST" action="{{ route('admin.ai.usecases.retire', $u->public_id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why retire?') }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Retire') }}</button></form>@endif</div>@endif
    </div></div>
    @endforeach
@stop
