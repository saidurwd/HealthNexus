@extends('layouts.adminlte')

@section('page_title', __('Transfers between stores'))

@section('page_content')
    @include('admin.inventory._flash')
    @can('inventory.transfer.create')
    <form method="POST" action="{{ route('admin.inventory.transfers.store') }}" class="card mb-3">@csrf
        <div class="card-header"><h3 class="card-title">{{ __('Ask to move stock') }}</h3></div>
        <div class="card-body row g-2">
            <div class="col-md-2"><select name="from" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ __('From') }}: {{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="to" class="form-select" required>@foreach($stores as $s)<option value="{{ $s->public_id }}">{{ __('To') }}: {{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="item" class="form-select" required>@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" placeholder="{{ __('Quantity') }}" required></div>
            <div class="col-md-2"><input name="notes" class="form-control" placeholder="{{ __('Note') }}" maxlength="300"></div>
            <div class="col-md-1"><button class="btn btn-danger w-100">{{ __('Ask') }}</button></div>
        </div></form>
    @endcan
    @forelse($rows as $t)
        <div class="card"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ $t->number }} · {{ $t->fromStore->name }} → {{ $t->toStore->name }}</h3><x-ui.status-badge :status="$t->status" /></div>
            <form method="POST" action="{{ route('admin.inventory.transfers.move', $t) }}">@csrf
                <ul class="list-group list-group-flush small">@foreach($t->lines as $l)<li class="list-group-item d-flex justify-content-between align-items-center gap-2"><span>{{ $l->item->code }} {{ $l->item->name }}</span><span>{{ $l->quantity + 0 }}
                    @if($t->status === 'dispatched')@can('inventory.transfer.receive')<input type="number" step="0.0001" min="0" max="{{ $l->quantity + 0 }}" name="received[{{ $l->id }}]" class="form-control form-control-sm d-inline-block" style="width:110px" placeholder="{{ __('Received') }}">@endcan @endif</span></li>@endforeach</ul>
                <div class="card-footer d-flex gap-2">
                    @if($t->status === 'requested')@can('inventory.transfer.dispatch')<button name="action" value="dispatch" class="btn btn-sm btn-primary">{{ __('Send out') }}</button>@endcan @endif
                    @if($t->status === 'dispatched')@can('inventory.transfer.receive')<button name="action" value="receive" class="btn btn-sm btn-success">{{ __('Receive') }}</button>@endcan @endif
                    @if(in_array($t->status, ['requested', 'dispatched']))@can('inventory.transfer.create')<button name="action" value="cancel" class="btn btn-sm btn-outline-secondary">{{ __('Cancel') }}</button>@endcan @endif
                </div></form></div>
    @empty<div class="alert alert-info">{{ __('No transfers.') }}</div>@endforelse
    {{ $rows->links() }}
@stop
