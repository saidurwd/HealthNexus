@extends('layouts.adminlte')

@section('page_title', __('Knowledge for answers'))

@section('page_content')
    @include('admin.ai._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('Answers are quoted from the current approved version of each hospital policy and procedure (Quality → Documents) and from the items approved here. A draft is never used.') }}</p>
    <div class="card mb-3"><ul class="list-group list-group-flush small">@forelse($items as $k)<li class="list-group-item"><div class="d-flex justify-content-between"><strong>{{ $k->title }}</strong><x-ui.status-badge :status="$k->status === 'approved' ? 'approved' : ($k->status === 'draft' ? 'draft' : 'retired')" :label="$H($k->status)" /></div><div class="text-muted">{{ \Illuminate\Support\Str::limit($k->body, 220) }}</div>
        <div class="d-flex gap-2 mt-1">@if($can['approve'] && $k->status === 'draft')@if($k->created_by === $me)<span class="text-muted">{{ __('A second person must approve it.') }}</span>@else<form method="POST" action="{{ route('admin.ai.knowledge.approve', $k->public_id) }}">@csrf<button class="btn btn-sm btn-success">{{ __('Approve') }}</button></form>@endif @endif
            @if($can['manage'] && $k->status !== 'retired')<form method="POST" action="{{ route('admin.ai.knowledge.retire', $k->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Retire') }}</button></form>@endif</div></li>@empty<li class="list-group-item text-muted">{{ __('Nothing added yet.') }}</li>@endforelse</ul></div>
    @if($can['manage'])<form method="POST" action="{{ route('admin.ai.knowledge.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Add a knowledge item') }}</h3></div><div class="card-body">@csrf<input name="title" class="form-control form-control-sm mb-2" placeholder="{{ __('Title') }}" required><textarea name="body" class="form-control form-control-sm mb-2" rows="5" required></textarea><button class="btn btn-sm btn-primary">{{ __('Save as a draft') }}</button></div></form>@endif
@stop
