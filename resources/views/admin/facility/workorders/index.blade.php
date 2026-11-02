@extends('layouts.adminlte')

@section('page_title', __('Work orders'))

@section('page_content')
    @include('admin.facility._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['open' => 'Open', 'to_check' => 'To be checked', 'done' => 'Done', 'verified' => 'Checked', 'all' => 'All'] as $k => $l)<a class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.facility.workorders', ['status' => $k]) }}">{{ __($l) }}</a>@endforeach</div>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('What') }}</th><th>{{ __('Equipment') }}</th><th>{{ __('Priority') }}</th><th>{{ __('Due') }}</th><th>{{ __('Engineer') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($orders as $o)<tr><td><a href="{{ route('admin.facility.workorders.show', $o->public_id) }}">{{ $o->number }}</a></td><td>{{ $o->title }} <span class="small text-muted">{{ $H($o->kind) }}</span></td><td>{{ $o->asset?->tag }}</td><td>@if($o->priority === 'urgent')<span class="badge text-bg-danger">{{ __('Urgent') }}</span>@else{{ $H($o->priority) }}@endif</td>
            <td>{{ $o->due_on?->format('d M') }} @if($o->due_on && $o->due_on->lt(today()) && in_array($o->status, ['open', 'in_progress', 'waiting_parts']))<x-ui.status-badge status="overdue" />@endif</td><td>{{ $o->assignee?->name }}</td><td><x-ui.status-badge :status="$o->status" /></td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No work orders.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $orders->links() }}</div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.facility.workorders.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Make a work order') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-3"><select name="asset_id" class="form-select form-select-sm"><option value="">{{ __('No equipment') }}</option>@foreach($assets as $a)<option value="{{ $a->id }}">{{ $a->tag }} · {{ $a->name }}</option>@endforeach</select></div><div class="col-md-2"><select name="kind" class="form-select form-select-sm">@foreach(['preventive', 'breakdown', 'inspection', 'other'] as $k)<option value="{{ $k }}">{{ $H($k) }}</option>@endforeach</select></div>
        <div class="col-md-4"><input name="title" class="form-control form-control-sm" placeholder="{{ __('What is to be done') }}" required></div><div class="col-md-1"><select name="priority" class="form-select form-select-sm">@foreach(['normal', 'urgent', 'low'] as $p)<option value="{{ $p }}">{{ $H($p) }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="date" name="due_on" class="form-control form-control-sm"></div><div class="col-12"><input name="description" class="form-control form-control-sm" placeholder="{{ __('Details') }}"></div><div class="col-12"><button class="btn btn-sm btn-primary">{{ __('Make') }}</button></div></div></form>@endif
@stop
