@extends('layouts.adminlte')

@section('page_title', __('Goods sent back to suppliers'))

@section('page_content')
    @include('admin.procurement._flash')
    @can('procurement.return.manage')
    <form method="POST" action="{{ route('admin.procurement.returns.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Send back what is in quarantine') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-3"><select name="supplier" class="form-select" required>@foreach($suppliers as $s)<option value="{{ $s->public_id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><select name="position" class="form-select" required>@foreach($held as $h)<option value="{{ $h->id }}">{{ $h->item->code }} {{ $h->item->name }} · {{ $batches[$h->batch_id] ?? '' }} ({{ $h->quantity_on_hand + 0 }})</option>@endforeach</select></div>
            <div class="col-md-1"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Qty') }}" required></div>
            <div class="col-md-3"><input name="reason" class="form-control" placeholder="{{ __('Reason') }}" required maxlength="300"></div><div class="col-md-1"><button class="btn btn-danger w-100">{{ __('Prepare') }}</button></div>
        </div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Return') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Items') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ $r->supplier->name }}</td><td>@foreach($r->lines as $l){{ $l->item->code }} × {{ $l->quantity + 0 }}<br>@endforeach</td><td>{{ $r->reason }}</td><td><x-ui.status-badge :status="$r->status" />@if($r->credit_note_number)<br><small>{{ $r->credit_note_number }} · {{ $r->credit_amount }}</small>@endif</td>
            <td>@can('procurement.return.manage')<form method="POST" action="{{ route('admin.procurement.returns.move', $r) }}" class="d-flex gap-1">@csrf
                @if($r->status === 'draft')<button name="action" value="dispatch" class="btn btn-sm btn-primary">{{ __('Send back') }}</button>@endif
                @if($r->status === 'dispatched')<input name="credit_note" class="form-control form-control-sm" placeholder="{{ __('Credit note') }}" required><input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" style="width:100px" placeholder="{{ __('Amount') }}" required><button name="action" value="credit" class="btn btn-sm btn-outline-success">{{ __('Credited') }}</button>@endif</form>@endcan</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No returns.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
