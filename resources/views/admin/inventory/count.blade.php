@extends('layouts.adminlte')

@section('page_title', __('Count :n', ['n' => $count->number]))

@section('page_content')
    @include('admin.inventory._flash')
    <p class="text-muted small">{{ $count->store->name }} · <x-ui.status-badge :status="$count->status" /> · {{ __('Count what is on the shelf and enter it. Nothing changes in stock until a second person approves.') }}</p>
    <form method="POST" action="{{ route('admin.inventory.counts.enter', $count) }}">@csrf
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Item') }}</th><th>{{ __('Batch') }}</th><th class="text-end">{{ __('System says') }}</th><th style="width:130px">{{ __('Counted') }}</th><th class="text-end">{{ __('Difference') }}</th><th>{{ __('Reason') }}</th></tr></thead>
            <tbody>@foreach($lines as $l)<tr><td>{{ $l->item->code }} {{ $l->item->name }}</td><td>{{ $batches[$l->batch_id] ?? '—' }}</td><td class="text-end">{{ $l->system_quantity + 0 }}</td>
                <td>@if($count->status === 'counting')<input type="number" step="0.0001" min="0" name="counted[{{ $l->id }}]" value="{{ $l->counted_quantity !== null ? $l->counted_quantity + 0 : '' }}" class="form-control form-control-sm">@else{{ $l->counted_quantity + 0 }}@endif</td>
                <td class="text-end {{ $l->variance != 0 && $l->variance !== null ? ($l->variance < 0 ? 'text-danger' : 'text-success') : '' }}">{{ $l->variance !== null ? $l->variance + 0 : '' }}</td>
                <td>@if($count->status === 'counting')<input name="reason[{{ $l->id }}]" value="{{ $l->reason }}" class="form-control form-control-sm" maxlength="200">@else{{ $l->reason }}@endif</td></tr>@endforeach</tbody></table></div>
            @if($count->status === 'counting')@can('inventory.count.perform')<div class="card-footer"><button class="btn btn-outline-primary">{{ __('Save counts') }}</button></div>@endcan @endif</div></form>
    <div class="d-flex gap-2">
        @if($count->status === 'counting')@can('inventory.count.perform')<form method="POST" action="{{ route('admin.inventory.counts.decide', $count) }}">@csrf<button name="action" value="submit" class="btn btn-danger">{{ __('Submit for approval') }}</button></form>@endcan @endif
        @if($count->status === 'submitted')@can('inventory.count.approve')<form method="POST" action="{{ route('admin.inventory.counts.decide', $count) }}" class="d-flex gap-2">@csrf<button name="action" value="approve" class="btn btn-success">{{ __('Approve and post differences') }}</button><button name="action" value="reject" class="btn btn-outline-danger">{{ __('Reject') }}</button></form>@endcan @endif
    </div>
@stop
