@extends('layouts.adminlte')

@section('page_title', __('Bed and cabin rates'))

@section('page_content')
    <style>@media print { .app-sidebar, .app-header, .app-footer, .d-print-none { display: none !important; } .app-main { margin: 0 !important; } @page { size: A4; margin: 12mm; } }</style>
    @php $money = fn ($v) => \App\Support\Dashboard\Numbers::value((float) $v, 'taka'); $N = fn ($v) => \App\Support\Dashboard\Numbers::number($v); $g = $card['gaps']; $cols = array_merge([''], $card['categories']); @endphp
    <x-ui.dashboard :question="__('What does a day in each kind of bed or cabin cost, and are there beds that would not be charged?')" :audience="__('For the admission desk, the billing office and patients who ask')" :as-of="\Carbon\CarbonImmutable::now()">
        <div class="d-flex flex-wrap gap-2 mb-3 d-print-none"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print the rate card (A4)') }}</button>
            @if($canBilling)<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.billing.price-lists.index') }}"><i class="bi bi-tags"></i> {{ __('Change prices in Billing') }}</a>@endif</div>
        @if($g['no_item'] || $g['zero_price'] || $g['orphan_items'] || ($g['untyped_beds'] > 0 && ! $g['untyped_priced']))
            <div class="alert alert-warning d-print-none"><strong><i class="bi bi-exclamation-triangle"></i> {{ __('Check these before relying on the card') }}</strong><ul class="mb-0 small">
                @foreach($g['no_item'] as $x)<li>{{ __(':name (:n beds) has no price in Billing, so a night in these beds would not be charged at all.', ['name' => $x['name'], 'n' => $N($x['beds'])]) }}</li>@endforeach
                @if($g['untyped_beds'] > 0 && ! $g['untyped_priced'])<li>{{ __(':n beds have no bed type and there is no general bed price, so they would not be charged.', ['n' => $N($g['untyped_beds'])]) }}</li>@endif
                @foreach($g['zero_price'] as $n)<li>{{ __(':name has a price of zero for some patients.', ['name' => $n]) }}</li>@endforeach
                @foreach($g['orphan_items'] as $code => $name)<li>{{ __('The bed price :code is for a bed type that does not exist.', ['code' => $code]) }}</li>@endforeach
            </ul></div>
        @endif
        <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Price of one day') }} <span class="small text-muted">· {{ \Carbon\CarbonImmutable::parse($card['on'])->format('d M Y') }}</span></h3></div>
            <div class="table-responsive"><table class="table table-sm mb-0 align-middle" data-list-count="{{ count($card['rows']) }}">
                <thead><tr><th>{{ __('Bed type') }}</th><th class="text-end">{{ __('Beds') }}</th>@foreach($cols as $cat)<th class="text-end">{{ $cat === '' ? __('Standard') : __(\Illuminate\Support\Str::headline($cat)) }}</th>@endforeach</tr></thead><tbody>
                @forelse($card['rows'] as $r)
                    <tr><td>{{ $r['type']->name }} <small class="text-muted">{{ $r['type']->code }}</small></td><td class="text-end">{{ $N($r['beds']) }}</td>
                    @foreach($cols as $cat)@php $p = $r['prices'][$cat] ?? null; @endphp
                        <td class="text-end">@if($p === null)<span class="text-danger">{{ __('No price') }}</span>@else{{ $money($p['price']) }}@if($p['source'] === 'price_list')<div class="small text-muted">{{ $p['list'] }}</div>@endif @endif</td>
                    @endforeach</tr>
                @empty<tr><td colspan="{{ 2 + count($cols) }}"><x-ui.empty-state :title="__('No bed type is set up.')" icon="bi-hospital" /></td></tr>@endforelse
            </tbody></table></div>
            <div class="card-footer small text-muted">{{ __('The price is what Billing would charge for the bed type per day, by the same rules it uses on a bill. Services, medicines and tests are charged on top.') }}</div></div>
        <div class="row g-3 d-print-none">
            <div class="col-lg-6"><form method="GET" class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('What would a stay cost?') }}</h3></div>
                <div class="card-body row g-2"><div class="col-md-5"><label class="form-label" for="e-type">{{ __('Bed type') }}</label><select id="e-type" name="bed_type" class="form-select" required>@foreach($card['rows'] as $r)<option value="{{ $r['type']->code }}" @selected(($asked['bed_type'] ?? '') === $r['type']->code)>{{ $r['type']->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label" for="e-days">{{ __('Days') }}</label><input id="e-days" type="number" name="days" min="1" max="365" value="{{ $asked['days'] ?? 3 }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label" for="e-cat">{{ __('Patient category') }}</label><select id="e-cat" name="category" class="form-select"><option value="">{{ __('Standard') }}</option>@foreach($card['categories'] as $cat)<option value="{{ $cat }}" @selected(($asked['category'] ?? '') === $cat)>{{ __(\Illuminate\Support\Str::headline($cat)) }}</option>@endforeach</select></div></div>
                <div class="card-footer d-flex align-items-center gap-3"><button class="btn btn-primary">{{ __('Work it out') }}</button>
                    @if($estimate === false)<span class="text-danger">{{ __('This bed type has no price in Billing.') }}</span>
                    @elseif($estimate)<span><strong>{{ $money($estimate['total']) }}</strong> <span class="text-muted">= {{ $N($estimate['days']) }} × {{ $money($estimate['per_day']) }} · {{ __('bed only') }}</span></span>@endif</div></form></div>
            <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Rooms with a rate label typed on them') }}</h3></div>
                <div class="card-body small">@if($labelled)<p class="text-muted">{{ __('A label typed on a room does not change any price. The price comes from the bed type of each bed.') }}</p>
                    <ul class="mb-0">@foreach(array_slice($labelled, 0, 12) as $x)<li>{{ $x['ward'] }} · {{ $x['room'] }}@if($x['vip']) <span class="badge text-bg-info">{{ __('VIP') }}</span>@endif — “{{ $x['label'] }}”</li>@endforeach</ul>@else<span class="text-muted">{{ __('None.') }}</span>@endif</div></div></div>
        </div>
    </x-ui.dashboard>
@stop
