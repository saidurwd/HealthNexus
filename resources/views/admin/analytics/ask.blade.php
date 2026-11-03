@extends('layouts.adminlte')

@section('page_title', __('Ask a question'))

@section('page_content')
    <div class="alert alert-info small">{{ __('Type a question in plain English. It is turned into a query you can check; it can only read, and it uses only what you are allowed to see. It is a helper, not an authority.') }}</div>
    <form method="GET" class="card card-body mb-3"><div class="input-group"><input name="q" class="form-control" maxlength="300" value="{{ $q }}" placeholder="{{ __('For example: net revenue by department last month') }}" aria-label="{{ __('Your question') }}" autofocus><button class="btn btn-primary">{{ __('Ask') }}</button></div></form>
    @if($answer)
        <div class="alert {{ $answer['understood'] ? 'alert-secondary' : 'alert-warning' }}">{{ __($answer['message']) }}</div>
        @if($answer['understood'] && $answer['result'])
            @php($cols = collect($answer['result']['columns']))
            <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>@foreach($cols as $c)<th class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ __($c['label']) }}</th>@endforeach</tr></thead><tbody>
                @forelse($answer['result']['rows'] as $r)<tr>@foreach($cols as $c)<td class="{{ $c['kind'] === 'measure' ? 'text-end' : '' }}">{{ $c['kind'] === 'measure' ? \App\Support\AnalyticsFormat::value($r[$c['key']] ?? null, $c['unit']) : ($r[$c['key']] ?? '—') }}</td>@endforeach</tr>@empty<tr><td colspan="{{ $cols->count() }}" class="text-muted">{{ __('Nothing for this period.') }}</td></tr>@endforelse</tbody></table></div></div>
            <details class="mb-3"><summary class="small text-muted">{{ __('The query it built') }}</summary><pre class="small bg-body-tertiary p-2 mt-2" style="white-space:pre-wrap">{{ json_encode($answer['query'], JSON_PRETTY_PRINT) }}

{{ $answer['result']['meta']['sql'] }}</pre><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.analytics.explore', array_merge(\Illuminate\Support\Arr::only($answer['query'], ['fact', 'measures', 'group_by', 'grain', 'from', 'to']))) }}">{{ __('Open it in the explorer') }}</a></details>
        @else
            <ul>@foreach($answer['suggestions'] as $s)<li><a href="{{ route('admin.analytics.ask', ['q' => $s]) }}">{{ $s }}</a></li>@endforeach</ul>
        @endif
    @endif
@stop
