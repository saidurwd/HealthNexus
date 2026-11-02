@extends('layouts.adminlte')

@section('page_title', __('Sterile services'))

@section('page_content')
    @include('admin.facility._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex mb-3"><a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('admin.facility.cssd.trace') }}"><i class="bi bi-search"></i> {{ __('Which packs were used in an operation?') }}</a></div>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Batch') }}</th><th>{{ __('Method') }}</th><th class="text-end">{{ __('Packs') }}</th><th>{{ __('Indicator') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($batches as $b)<tr><td><a href="{{ route('admin.facility.cssd.show', $b->public_id) }}">{{ $b->number }}</a></td><td>{{ strtoupper($b->method) }}</td><td class="text-end">{{ $b->items_count }}</td><td>{{ $b->indicator_result ? $H($b->indicator_result) : '—' }}</td><td><x-ui.status-badge :status="$b->status" /></td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('No batches yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $batches->links() }}</div></div>
    @if($canManage)
    <form method="POST" action="{{ route('admin.facility.cssd.load') }}" class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Load a new batch') }}</h3></div><div class="card-body">@csrf
        <div class="row g-2 mb-2"><div class="col-md-3"><select name="method" class="form-select form-select-sm">@foreach($methods as $m)<option value="{{ $m }}">{{ strtoupper($m) }}</option>@endforeach</select></div><div class="col-md-5"><select name="asset_id" class="form-select form-select-sm"><option value="">{{ __('Steriliser (optional)') }}</option>@foreach($machines as $m)<option value="{{ $m->id }}">{{ $m->tag }} · {{ $m->name }}</option>@endforeach</select></div></div>
        @foreach($sets as $n => $s)<div class="row g-1 mb-1"><div class="col-9 small">{{ $s->code }} · {{ $s->name }}<input type="hidden" name="sets[{{ $n }}]" value="{{ $s->id }}"></div><div class="col-3"><input type="number" min="0" max="50" name="qty[{{ $n }}]" class="form-control form-control-sm" value="0"></div></div>@endforeach
        <button class="btn btn-sm btn-primary mt-2">{{ __('Load') }}</button></div></form>
    <form method="POST" action="{{ route('admin.facility.cssd.sets') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Instrument sets') }}</h3></div><div class="card-body row g-2">@csrf<div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="{{ __('Code') }}" required></div><div class="col-md-6"><input name="name" class="form-control form-control-sm" placeholder="{{ __('Name') }}" required></div><div class="col-md-2"><input type="number" min="0" name="instrument_count" class="form-control form-control-sm" placeholder="{{ __('Instruments') }}"></div><div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">{{ __('Save') }}</button></div></div></form>@endif
@stop
