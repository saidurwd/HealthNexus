@extends('layouts.adminlte')

@section('page_title', $a->name)

@section('page_content')
    @include('admin.quality._flash')
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center"><x-ui.status-badge :status="$a->status" /><span class="small text-muted">{{ $a->standard->name }}</span> @if($score !== null)<span class="badge text-bg-primary">{{ $score }}%</span>@endif
        @if($a->status !== 'completed' && $canManage)<form method="POST" action="{{ route('admin.quality.accreditation.complete', $a->public_id) }}" class="ms-auto">@csrf<button class="btn btn-sm btn-success">{{ __('Complete the assessment') }}</button></form>@endif</div>
    @foreach($a->results->sortBy('item.sort')->groupBy(fn ($r) => $r->item->chapter) as $chapter => $rows)
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __($chapter ?: 'General') }}</h3></div><ul class="list-group list-group-flush">
        @foreach($rows as $r)<li class="list-group-item"><div class="d-flex justify-content-between gap-2"><span><strong>{{ $r->item->code }}</strong> {{ $r->item->requirement }}</span><x-ui.status-badge :status="$r->result" :label="$r->result === 'na' ? __('Not applicable') : null" /></div>
            @if($r->evidence)<div class="small text-muted">{{ $r->evidence }}</div>@endif
            @if($r->action)<div class="small">{{ __('Action') }}: {{ $r->action->number }} · {{ $r->action->owner?->name }} · <x-ui.status-badge :status="$r->action->status" /></div>@endif
            @if($a->status !== 'completed' && $canManage)<form method="POST" action="{{ route('admin.quality.accreditation.record', [$a->public_id, $r->id]) }}" class="d-flex gap-1 mt-1">@csrf<select name="result" class="form-select form-select-sm" style="width:160px">@foreach($results as $o)<option value="{{ $o }}" @selected($r->result === $o)>{{ $o === 'na' ? __('Not applicable') : __(\Illuminate\Support\Str::headline($o)) }}</option>@endforeach</select><input name="evidence" class="form-control form-control-sm" placeholder="{{ __('What you saw (the evidence)') }}" value="{{ $r->evidence }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Save') }}</button></form>
                @if(in_array($r->result, ['partial', 'non_compliant']) && ! $r->action_id)<form method="POST" action="{{ route('admin.quality.accreditation.gap', [$a->public_id, $r->id]) }}" class="d-flex gap-1 mt-1">@csrf<select name="owner_id" class="form-select form-select-sm" style="width:200px">@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select><input type="date" name="due_on" class="form-control form-control-sm" style="width:150px" min="{{ today()->toDateString() }}"><input name="description" class="form-control form-control-sm" placeholder="{{ __('What will close the gap') }}"><button class="btn btn-sm btn-warning">{{ __('Raise an action') }}</button></form>@endif
            @endif</li>@endforeach</ul></div>
    @endforeach
@stop
