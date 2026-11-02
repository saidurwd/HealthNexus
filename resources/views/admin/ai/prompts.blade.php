@extends('layouts.adminlte')

@section('page_title', __('Prompts'))

@section('page_content')
    @include('admin.ai._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('The instruction given to a model for each function. A change is a new version that a second person activates; every request records the version it used. Without an active version the built-in text is used.') }}</p>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Function') }}</th><th>{{ __('Version') }}</th><th>{{ __('Instruction') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($prompts as $p)<tr><td>{{ $H($p->use_case_code) }}</td><td>v{{ $p->version }}</td><td class="small">{{ \Illuminate\Support\Str::limit($p->template, 140) }}</td><td><x-ui.status-badge :status="$p->status === 'active' ? 'active' : ($p->status === 'draft' ? 'draft' : 'retired')" :label="$H($p->status)" /></td>
            <td>@if($canManage && $p->status === 'draft')@if($p->created_by === $me)<span class="small text-muted">{{ __('A second person must activate it.') }}</span>@else<form method="POST" action="{{ route('admin.ai.prompts.activate', $p->id) }}">@csrf<button class="btn btn-sm btn-success">{{ __('Activate') }}</button></form>@endif @endif</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No versions yet: the built-in text is used.') }}</td></tr>@endforelse</tbody></table></div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.ai.prompts.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Write a new version') }}</h3></div><div class="card-body">@csrf<select name="use_case_code" class="form-select form-select-sm mb-2">@foreach($codes as $c)<option value="{{ $c }}">{{ $H($c) }}</option>@endforeach</select>
        <textarea name="template" class="form-control form-control-sm mb-2" rows="4" required placeholder="{{ __('Write the instruction. The word {language} is replaced by the language of the answer.') }}"></textarea><button class="btn btn-sm btn-primary">{{ __('Save as a draft') }}</button></div></form>@endif
@stop
