@extends('layouts.adminlte')

@section('page_title', __('Receipt :n', ['n' => $receipt->number]))

@section('page_content')
    @include('admin.procurement._flash')
    <p class="small text-muted">{{ $receipt->supplier->name }} · {{ __('order') }} {{ $receipt->order->number }} · <x-ui.status-badge :status="$receipt->status" /> {{ $receipt->delivery_note ? '· '.$receipt->delivery_note : '' }}</p>
    <form method="POST" action="{{ route('admin.procurement.receipts.inspect', $receipt) }}">@csrf
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Item') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Expires') }}</th><th class="text-end">{{ __('Arrived') }}</th><th class="text-end">{{ __('Accepted') }}</th><th style="width:120px">{{ __('Rejected') }}</th><th>{{ __('Why rejected') }}</th></tr></thead>
            <tbody>@foreach($receipt->lines as $l)<tr><td>{{ $l->item->code }} {{ $l->item->name }}</td><td>{{ $l->batch_no ?? '—' }}</td><td>{{ $l->expiry_date?->toDateString() ?? '—' }}</td><td class="text-end">{{ $l->received_quantity + 0 }}</td><td class="text-end">{{ $receipt->status === 'posted' ? $l->accepted_quantity + 0 : '' }}</td>
                <td>@if($receipt->status === 'pending_inspection')<input type="number" step="0.0001" min="0" max="{{ $l->received_quantity + 0 }}" name="rejected[{{ $l->id }}]" value="0" class="form-control form-control-sm">@else{{ $l->rejected_quantity + 0 }}@endif</td>
                <td>@if($receipt->status === 'pending_inspection')<input name="reason[{{ $l->id }}]" class="form-control form-control-sm" maxlength="200">@else{{ $l->reject_reason }}@endif</td></tr>@endforeach</tbody></table></div>
            @if($receipt->status === 'pending_inspection')@can('procurement.grn.inspect')<div class="card-footer"><button class="btn btn-danger">{{ __('Inspect and put into stock') }}</button> <span class="small text-muted ms-2">{{ __('Rejected goods go to quarantine to be sent back.') }}</span></div>@endcan @endif</div></form>
@stop
