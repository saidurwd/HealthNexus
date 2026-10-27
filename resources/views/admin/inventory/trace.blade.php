@extends('layouts.adminlte')

@section('page_title', __('Where batch :b went', ['b' => $batch->batch_no]))

@section('page_content')
    @include('admin.inventory._flash')
    <div class="card mb-3"><div class="card-body small">{{ $batch->item->code }} {{ $batch->item->name }} · {{ __('Expires') }} {{ $batch->expiry_date?->toDateString() ?? '—' }} · <x-ui.status-badge :status="$batch->status" /> {{ $batch->status_reason }}
        @can('inventory.expiry.manage')@if($batch->status === 'active')<form method="POST" action="{{ route('admin.inventory.batches.hold', $batch) }}" class="d-flex gap-2 mt-2">@csrf<input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('Why hold it?') }}" required><button class="btn btn-sm btn-outline-warning">{{ __('Put on hold') }}</button></form>@endif @endcan</div></div>
    <div class="row g-3">
        <div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Still in stores') }}</h3></div><ul class="list-group list-group-flush small">@forelse($trace['on_hand'] as $r)<li class="list-group-item d-flex justify-content-between"><span>{{ $r['store'] }}</span><span>{{ $r['quantity'] + 0 }}</span></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div></div>
        <div class="col-md-6"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Issued to departments') }}</h3></div><ul class="list-group list-group-flush small">@forelse($trace['issued'] as $r)<li class="list-group-item d-flex justify-content-between"><span>{{ $r->department }}</span><span>{{ $r->quantity + 0 }}</span></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul></div>
            <div class="card"><div class="card-body small">{{ __('Used at the point of care') }}: {{ $trace['consumed']['quantity'] ?? 0 }} {{ __('for') }} {{ $trace['consumed']['patients'] ?? 0 }} {{ __('patient(s)') }} · {{ __('Moved in :n transfer line(s)', ['n' => $trace['transferred']]) }}</div></div></div>
    </div>
@stop
