@extends('layouts.adminlte')

@section('page_title', __('What to buy next'))

@section('page_content')
    @include('admin.procurement._flash')
    <p class="text-muted small">{{ __('Items at or under their reorder level, counting what is already on order. Quantities come from real use over the last 90 days.') }}</p>
    <form method="POST" action="{{ route('admin.procurement.reorder.raise') }}">@csrf
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th></th><th>{{ __('Item') }}</th><th class="text-end">{{ __('On hand') }}</th><th class="text-end">{{ __('On order') }}</th><th class="text-end">{{ __('Used per day') }}</th><th class="text-end">{{ __('Reorder level') }}</th><th class="text-end">{{ __('Suggested to buy') }}</th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td><input type="checkbox" name="items[]" value="{{ $r['item_id'] }}" checked></td><td>{{ $r['code'] }} {{ $r['name'] }}</td><td class="text-end">{{ $r['on_hand'] }}</td><td class="text-end">{{ $r['on_order'] }}</td><td class="text-end">{{ $r['daily_use'] }}</td><td class="text-end">{{ $r['reorder_point'] }}</td><td class="text-end">{{ $r['suggested_purchase_units'] }} {{ $r['purchase_uom'] }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('Nothing needs ordering.') }}</td></tr>@endforelse</tbody></table></div>
        @if($rows)@can('procurement.requisition.create')<div class="card-footer d-flex gap-2"><select name="store" class="form-select w-auto">@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select><button class="btn btn-danger">{{ __('Raise a requisition for the ticked items') }}</button></div>@endcan @endif</div></form>
@stop
