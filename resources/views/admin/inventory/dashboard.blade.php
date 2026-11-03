@extends('layouts.adminlte')

@section('page_title', __('Stores and supplies'))

@section('page_content')
    @include('admin.inventory._flash')
    <x-ui.dashboard :question="__('Are the stores stocked, safe to use and being paid for correctly?')" :audience="__('For the storekeeper and the purchasing officer')" :as-of="$asOf">
    @php $tiles = [
        [__('Value of stock held'), (float) $d['stock_value'], false, null, 'taka'],
        [__('Items below their reorder level'), $d['below_reorder'], $d['below_reorder'] > 0, route('admin.procurement.reorder')],
        [__('Expiring in 90 days'), $d['expiring_90'], $d['expiring_90'] > 0, route('admin.inventory.expiry')],
        [__('Expired but still in a store'), $d['expired_in_stock'], $d['expired_in_stock'] > 0, route('admin.inventory.expiry', ['days' => 30])],
        [__('Batches on hold or recalled'), $d['quarantined_batches'], false, route('admin.inventory.expiry')],
        [__('Orders on their way'), $d['open_orders'], false, route('admin.procurement.orders')],
        [__('Orders waiting for approval'), $d['orders_awaiting_approval'], $d['orders_awaiting_approval'] > 0, route('admin.procurement.orders', ['status' => 'pending_approval'])],
        [__('Requisitions waiting for approval'), $d['requisitions_awaiting'], $d['requisitions_awaiting'] > 0, route('admin.procurement.requisitions', ['status' => 'submitted'])],
        [__('Stock changes waiting for a second person'), $d['adjustments_pending'], $d['adjustments_pending'] > 0, route('admin.inventory.adjustments')],
        [__('Transfers in transit'), $d['transfers_in_transit'], false, route('admin.inventory.transfers')],
        [__('Emergency orders not yet approved in time'), $d['unratified_emergency'], $d['unratified_emergency'] > 0, route('admin.procurement.orders')],
        [__('Supplier invoices that do not match'), $d['invoices_to_review'], $d['invoices_to_review'] > 0, route('admin.procurement.invoices')],
        [__('Supplier documents ending in 30 days'), $d['documents_expiring_30'], $d['documents_expiring_30'] > 0, route('admin.procurement.suppliers')],
        [__('Active items'), $d['items'], false, route('admin.inventory.items')],
    ]; @endphp
    <x-ui.tile-grid :tiles="$tiles" />
    </x-ui.dashboard>
@stop
