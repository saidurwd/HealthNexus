@extends('layouts.adminlte')

@section('page_title', __('AI request log'))

@section('page_content')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('Every call to an AI function, whether it was allowed, refused or failed. The log only grows. It holds the redacted facts the model was given, not the patient\'s name.') }}</p>
    <form class="row g-2 mb-3" method="GET"><div class="col-md-3"><select name="outcome" class="form-select form-select-sm"><option value="">{{ __('Any outcome') }}</option>@foreach(['completed', 'blocked', 'failed'] as $o)<option value="{{ $o }}" @selected(($filters['outcome'] ?? '') === $o)>{{ $H($o) }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-sm btn-primary">{{ __('Filter') }}</button></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Who') }}</th><th>{{ __('Function') }}</th><th>{{ __('Model') }}</th><th>{{ __('Data') }}</th><th>{{ __('Outcome') }}</th><th class="text-end">{{ __('Lines removed') }}</th></tr></thead><tbody>
        @forelse($rows as $r)<tr><td>{{ $r->created_at?->setTimezone('Asia/Dhaka')->format('d M H:i:s') }}</td><td>{{ $users[$r->user_id] ?? '—' }}</td><td>{{ $H($r->use_case_code) }}</td><td class="small">{{ $r->model_name ? $r->model_name.' v'.$r->model_version : '—' }} @if($r->prompt_version)<span class="text-muted">· {{ __('prompt') }} v{{ $r->prompt_version }}</span>@endif</td><td>{{ $r->mode ? $H($r->mode) : '—' }}</td>
            <td><x-ui.status-badge :status="$r->outcome === 'completed' ? 'completed' : ($r->outcome === 'blocked' ? 'locked' : 'failed')" :label="$H($r->outcome)" />@if($r->blocked_reason) <span class="small text-muted">{{ $r->blocked_reason }}</span>@endif</td><td class="text-end">{{ $r->suppressed }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('Nothing logged.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
