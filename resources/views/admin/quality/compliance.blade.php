@extends('layouts.adminlte')

@section('page_title', __('Licences and inspections'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Item') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Authority') }}</th><th>{{ __('Due') }}</th><th>{{ __('Standing') }}</th><th>{{ __('Owner') }}</th><th></th></tr></thead><tbody>
        @forelse($items as $it)<tr><td>{{ $it->title }} <span class="small text-muted">{{ $it->reference }}</span></td><td>{{ __(\Illuminate\Support\Str::headline($it->kind)) }}</td><td>{{ $it->authority }}</td><td>{{ $it->due_on->format('d M Y') }}</td><td><x-ui.status-badge :status="$service->standing($it)" /></td><td>{{ $it->owner?->name }}</td>
            <td style="min-width:240px">@if($canManage)<form method="POST" action="{{ route('admin.quality.compliance.done', $it->public_id) }}" class="d-flex gap-1">@csrf<input type="date" name="done_on" class="form-control form-control-sm" value="{{ today()->toDateString() }}">@unless($it->interval_months)<input type="date" name="next_due" class="form-control form-control-sm" required>@endunless<button class="btn btn-sm btn-success">{{ __('Done') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('Nothing in the calendar yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.quality.compliance.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Add to the calendar') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-4"><input name="title" class="form-control form-control-sm" placeholder="{{ __('What (for example: operating licence)') }}" required></div><div class="col-md-2"><select name="kind" class="form-select form-select-sm">@foreach($kinds as $k)<option value="{{ $k }}">{{ __(\Illuminate\Support\Str::headline($k)) }}</option>@endforeach</select></div>
        <div class="col-md-3"><input name="authority" class="form-control form-control-sm" placeholder="{{ __('Authority') }}"></div><div class="col-md-3"><input name="reference" class="form-control form-control-sm" placeholder="{{ __('Reference number') }}"></div>
        <div class="col-md-3"><label class="small">{{ __('Due') }}</label><input type="date" name="due_on" class="form-control form-control-sm" required></div><div class="col-md-3"><label class="small">{{ __('Repeats every (months)') }}</label><input type="number" name="interval_months" class="form-control form-control-sm" min="1"></div>
        <div class="col-md-4"><label class="small">{{ __('Looked after by') }}</label><select name="owner_id" class="form-select form-select-sm">@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">{{ __('Add') }}</button></div></div></form>@endif
@stop
