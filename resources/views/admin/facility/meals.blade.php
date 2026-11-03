@extends('layouts.adminlte')

@section('page_title', __('Meal lists'))

@section('page_content')
    @include('admin.facility._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <form class="row g-2 mb-3" method="GET"><div class="col-md-3"><input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}"></div><div class="col-md-3"><select name="meal" class="form-select form-select-sm">@foreach($meals as $m)<option value="{{ $m }}" @selected($m === $meal)>{{ $H($m) }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="ward_id" class="form-select form-select-sm"><option value="">{{ __('Every ward') }}</option>@foreach($wards as $w)<option value="{{ $w->id }}" @selected((string) $wardId === (string) $w->id)>{{ $w->name }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-sm btn-primary">{{ __('Show') }}</button></div></form>
    <div class="mb-2"><x-ui.status-badge :status="$list->status === 'served' ? 'completed' : 'pending'" :label="$list->status === 'served' ? __('Closed') : __('Being served')" /> <span class="small text-muted">{{ $list->trays->count() }} {{ __('trays') }}</span></div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Diet') }}</th><th>{{ __('Texture') }}</th><th>{{ __('Allergies') }}</th><th>{{ __('Tray') }}</th><th></th></tr></thead><tbody>
        @forelse($list->trays as $t)<tr><td>{{ $t->order->patient?->enterprise_patient_no }} {{ $t->order->patient?->first_name }}</td><td>{{ $t->order->diet->name }}</td><td>{{ $H($t->order->texture) }}</td><td>@if($t->order->allergies)<strong class="text-danger">{{ $t->order->allergies }}</strong>@else—@endif</td><td><x-ui.status-badge :status="$t->status === 'planned' ? 'pending' : ($t->status === 'served' ? 'completed' : ($t->status === 'held' ? 'locked' : 'cancelled'))" :label="$H($t->status)" /></td>
            <td style="min-width:240px">@if($canServe && $list->status !== 'served')<form method="POST" action="{{ route('admin.facility.meals.serve', $t->id) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"><button name="status" value="served" class="btn btn-sm btn-success">{{ __('Served') }}</button><button name="status" value="refused" class="btn btn-sm btn-outline-secondary">{{ __('Refused') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No diets for this meal.') }}</td></tr>@endforelse</tbody></table></div>
        @if($canServe && $list->status !== 'served')<div class="card-footer"><form method="POST" action="{{ route('admin.facility.meals.close', $list->public_id) }}">@csrf<button class="btn btn-sm btn-primary">{{ __('Close this meal') }}</button></form></div>@endif</div>
@stop
