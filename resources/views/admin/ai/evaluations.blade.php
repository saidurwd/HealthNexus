@extends('layouts.adminlte')

@section('page_title', __('Evaluations'))

@section('page_content')
    <p class="small text-muted">{{ __('A model is approved only after a run that meets the thresholds: it found the facts it had to, nothing unsupported got through, nothing forbidden leaked, and the set included Bangla.') }}</p>
    <div class="row g-3">
        <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Sets') }}</h3></div><ul class="list-group list-group-flush small">@forelse($sets as $s)<li class="list-group-item d-flex justify-content-between">{{ $s->name }}<span class="badge text-bg-light border">{{ $s->cases_count }} {{ __('cases') }}</span></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div></div>
        <div class="col-lg-8"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Runs') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Model') }}</th><th>{{ __('Set') }}</th><th class="text-end">{{ __('Recall') }}</th><th class="text-end">{{ __('Unsupported') }}</th><th class="text-end">{{ __('Leaks') }}</th><th>{{ __('Result') }}</th></tr></thead><tbody>
            @forelse($runs as $r)<tr><td>{{ $r->created_at?->setTimezone('Asia/Dhaka')->format('d M H:i') }}</td><td>{{ $r->model->name }} v{{ $r->model->version }}</td><td class="small">{{ $r->set->name }}</td><td class="text-end">{{ round(100 * (float) $r->recall) }}%</td><td class="text-end">{{ $r->unsupported }}</td><td class="text-end">{{ $r->forbidden_hits }}</td><td><x-ui.status-badge :status="$r->passed ? 'compliant' : 'non_compliant'" :label="$r->passed ? __('Passed') : __('Failed')" /></td></tr>
            @empty<tr><td colspan="7" class="text-muted">{{ __('No runs yet.') }}</td></tr>@endforelse</tbody></table></div></div></div>
    </div>
@stop
