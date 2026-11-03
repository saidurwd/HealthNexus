@extends('layouts.adminlte')

@section('page_title', __('OPD ticket and fees'))

@section('page_content')
    <style>@media print { .app-sidebar, .app-header, .app-footer, .d-print-none { display: none !important; } .app-main { margin: 0 !important; } @page { size: A4; margin: 12mm; } }</style>
    @php $cols = array_merge([''], $card['categories']); $money = fn ($v) => (float) $v <= 0 ? __('Free') : \App\Support\Dashboard\Numbers::value((float) $v, 'taka'); @endphp
    <x-ui.dashboard :question="__('What does an outpatient visit cost, and who pays nothing?')" :audience="__('For the ticket counter, the billing office and patients who ask')" :as-of="\Carbon\CarbonImmutable::now()">
        <div class="d-flex flex-wrap gap-2 mb-3 d-print-none"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print (A4)') }}</button>
            @if($canBilling)<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.billing.price-lists.index') }}"><i class="bi bi-tags"></i> {{ __('Change prices in Billing') }}</a>@endif</div>
        @if($card['missing'])<div class="alert alert-warning d-print-none small"><i class="bi bi-exclamation-triangle"></i> {{ __('No active Billing item has the code: :codes. Add it in Billing, or change the codes shown here in Settings (rates.opd_item_codes).', ['codes' => implode(', ', $card['missing'])]) }}</div>@endif
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0 align-middle" data-list-count="{{ count($card['rows']) }}">
            <thead><tr><th>{{ __('Fee') }}</th>@foreach($cols as $cat)<th class="text-end">{{ $cat === '' ? __('Standard') : __(\Illuminate\Support\Str::headline($cat)) }}</th>@endforeach</tr></thead><tbody>
            @forelse($card['rows'] as $r)
                <tr><td>{{ $r['item']->name }} <small class="text-muted">{{ $r['item']->item_code }}</small></td>
                @foreach($cols as $cat)@php $p = $r['prices'][$cat] ?? null; @endphp<td class="text-end">{{ $money($p['price']) }}@if($p['source'] === 'price_list')<div class="small text-muted">{{ $p['list'] }}</div>@endif</td>@endforeach</tr>
            @empty<tr><td colspan="{{ 1 + count($cols) }}"><x-ui.empty-state :title="__('No fee is set up.')" icon="bi-ticket-perforated" /></td></tr>@endforelse
        </tbody></table></div>
        <div class="card-footer small text-muted">{{ __('A price of zero is shown as Free. Which patients belong to a category, and which categories are free, is decided in Billing\'s price lists and by the hospital.') }}</div></div>
    </x-ui.dashboard>
@stop
