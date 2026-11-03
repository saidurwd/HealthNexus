@extends('layouts.adminlte')

@section('page_title', __('Diets and kitchen'))

@section('page_content')
    @include('admin.facility._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <div class="d-flex mb-3"><a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('admin.facility.meals') }}"><i class="bi bi-list-check"></i> {{ __('Meal lists') }}</a></div>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Diets in force') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Diet') }}</th><th>{{ __('Texture') }}</th><th>{{ __('Allergies') }}</th><th>{{ __('Since') }}</th><th></th></tr></thead><tbody>
                @forelse($orders as $o)<tr><td>{{ $o->patient?->enterprise_patient_no }} {{ $o->patient?->first_name }}</td><td>{{ $o->diet->name }}</td><td>{{ $H($o->texture) }}</td><td>@if($o->allergies)<span class="text-danger">{{ $o->allergies }}</span>@else—@endif</td><td>{{ $o->starts_on->format('d M') }}</td>
                    <td>@if($can['order'])<form method="POST" action="{{ route('admin.facility.diets.stop', $o->public_id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Stop') }}</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('No diets ordered.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $orders->links() }}</div></div>
            @if($can['order'])<form method="POST" action="{{ route('admin.facility.diets.order') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Order a diet') }}</h3></div><div class="card-body row g-2">@csrf
                <div class="col-md-4"><select name="admission_id" class="form-select form-select-sm">@foreach($admitted as $p)<option value="{{ $p->id }}">{{ $p->enterprise_patient_no }} · {{ $p->first_name }} {{ $p->last_name }}</option>@endforeach</select></div><div class="col-md-3"><select name="diet_type_id" class="form-select form-select-sm">@foreach($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="texture" class="form-select form-select-sm">@foreach($textures as $t)<option value="{{ $t }}">{{ $H($t) }}</option>@endforeach</select></div><div class="col-md-2"><select name="ward_id" class="form-select form-select-sm"><option value="">{{ __('Ward') }}</option>@foreach($wards as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><input name="allergies" class="form-control form-control-sm" placeholder="{{ __('Allergies') }}"></div><div class="col-md-4"><input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}"></div><div class="col-md-2"><button class="btn btn-sm btn-primary w-100">{{ __('Order') }}</button></div></div></form>@endif
        </div>
        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __("Today's kitchen requisition") }}</h3></div><ul class="list-group list-group-flush small">@forelse($requisition as $label => $meals)<li class="list-group-item"><strong>{{ $label }}</strong><div class="text-muted">@foreach($meals as $meal => $n){{ $H($meal) }}: {{ $n }} @endforeach</div></li>@empty<li class="list-group-item text-muted">{{ __('No trays to make.') }}</li>@endforelse</ul></div>
            @if($can['kitchen'])<form method="POST" action="{{ route('admin.facility.diets.type') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Kinds of diet') }}</h3></div><div class="card-body row g-1">@csrf<div class="col-4"><input name="code" class="form-control form-control-sm" placeholder="{{ __('Code') }}" required></div><div class="col-8"><input name="name" class="form-control form-control-sm" placeholder="{{ __('Name') }}" required></div><div class="col-12"><button class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button></div></div></form>@endif
        </div>
    </div>
@stop
