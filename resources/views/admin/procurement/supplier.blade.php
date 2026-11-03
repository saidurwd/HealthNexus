@extends('layouts.adminlte')

@section('page_title', $supplier->name)

@section('page_content')
    @include('admin.procurement._flash')
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center small">
        <x-ui.status-badge :status="$supplier->status" /> <span>{{ $supplier->code }}</span> <span>{{ $supplier->contact_name }} {{ $supplier->phone }} {{ $supplier->email }}</span> <span>{{ __('VAT / BIN') }}: {{ $supplier->bin_number ?? '—' }}</span> <span>{{ __('Pays in :d days', ['d' => $supplier->payment_terms_days]) }}</span>
        @if($supplier->status_reason)<span class="text-danger">{{ $supplier->status_reason }}</span>@endif
        @if($gaps)<span class="text-danger">{{ __('Missing or out of date') }}: {{ implode(', ', array_map(fn ($g) => __(\Illuminate\Support\Str::headline($g)), $gaps)) }}</span>@endif
        @can('procurement.supplier.qualify')<form method="POST" action="{{ route('admin.procurement.suppliers.status', $supplier) }}" class="ms-auto d-flex gap-2">@csrf
            @if(in_array($supplier->status, ['prospective', 'suspended']))<button name="action" value="qualify" class="btn btn-sm btn-success">{{ __('Qualify') }}</button>@endif
            @if($supplier->status !== 'disqualified')<input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('Reason') }}"><button name="action" value="suspend" class="btn btn-sm btn-outline-warning">{{ __('Suspend') }}</button><button name="action" value="disqualify" class="btn btn-sm btn-outline-danger">{{ __('Disqualify') }}</button>@endif</form>@endcan
    </div></div>
    <div class="row g-3">
        <div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Documents') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($documents as $d)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\Illuminate\Support\Str::headline($d->doc_type)) }} {{ $d->number }}</span><span class="{{ $d->expires_on && $d->expires_on->lt(today()) ? 'text-danger' : '' }}">{{ $d->expires_on ? __('until').' '.$d->expires_on->toDateString() : __('no expiry') }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No documents.') }}</li>@endforelse</ul>
            @can('procurement.supplier.manage')<form method="POST" action="{{ route('admin.procurement.suppliers.documents', $supplier) }}" class="card-footer d-flex flex-wrap gap-2">@csrf
                <select name="doc_type" class="form-select form-select-sm w-auto">@foreach($types as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select><input name="number" class="form-control form-control-sm w-auto" placeholder="{{ __('Number') }}">
                <input type="date" name="issued_on" class="form-control form-control-sm w-auto" title="{{ __('Issued') }}"><input type="date" name="expires_on" class="form-control form-control-sm w-auto" title="{{ __('Expires') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Add') }}</button></form>@endcan</div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Agreed prices') }}</h3></div>
                <ul class="list-group list-group-flush small">@forelse($contracts as $c)<li class="list-group-item d-flex justify-content-between"><span>{{ $c->item->code }} {{ $c->item->name }}</span><span>{{ $c->unit_price + 0 }} · {{ $c->valid_from->toDateString() }} → {{ $c->valid_to?->toDateString() ?? __('open') }}</span></li>@empty<li class="list-group-item text-muted">{{ __('No agreed prices.') }}</li>@endforelse</ul>
                @can('procurement.contract.manage')<form method="POST" action="{{ route('admin.procurement.suppliers.contracts', $supplier) }}" class="card-footer d-flex flex-wrap gap-2">@csrf
                    <select name="item" class="form-select form-select-sm w-auto">@foreach($items as $i)<option value="{{ $i->public_id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select><input type="number" step="0.0001" name="unit_price" class="form-control form-control-sm" style="width:110px" placeholder="{{ __('Price') }}" required>
                    <input type="date" name="valid_from" class="form-control form-control-sm w-auto" required><input type="date" name="valid_to" class="form-control form-control-sm w-auto"><button class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button></form>@endcan</div></div>
        <div class="col-lg-5"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Scorecard') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($evaluations as $e)<li class="list-group-item d-flex justify-content-between"><span>{{ $e->period_end->toDateString() }}</span><span>{{ __('delivery') }} {{ $e->delivery_score + 0 }} · {{ __('quality') }} {{ $e->quality_score + 0 }} · {{ __('price') }} {{ $e->price_score + 0 }} = <strong>{{ $e->total_score + 0 }}</strong></span></li>@empty<li class="list-group-item text-muted">{{ __('Not scored yet.') }}</li>@endforelse</ul>
            @can('procurement.supplier.manage')<form method="POST" action="{{ route('admin.procurement.suppliers.evaluate', $supplier) }}" class="card-footer d-flex flex-wrap gap-2">@csrf
                @foreach(['delivery' => __('Delivery'), 'quality' => __('Quality'), 'price' => __('Price')] as $k => $l)<input type="number" step="0.1" min="0" max="5" name="{{ $k }}" class="form-control form-control-sm" style="width:90px" placeholder="{{ $l }}" required>@endforeach<button class="btn btn-sm btn-outline-primary">{{ __('Save (0–5)') }}</button></form>@endcan</div></div>
    </div>
@stop
