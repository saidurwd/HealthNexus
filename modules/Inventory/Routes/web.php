<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Web\InventoryDeskController;
use Modules\Inventory\Http\Controllers\Web\InventoryMovementController;
use Modules\Inventory\Http\Controllers\Web\InventoryStockController;
use Modules\Inventory\Http\Controllers\Web\ProcurementOrderController;
use Modules\Inventory\Http\Controllers\Web\ProcurementReceivingController;
use Modules\Inventory\Http\Controllers\Web\ProcurementSupplierController;

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryDeskController::class, 'dashboard'])->name('dashboard');
    Route::get('items', [InventoryDeskController::class, 'items'])->name('items');
    Route::post('items', [InventoryDeskController::class, 'storeItem'])->name('items.store');
    Route::get('items/{item}', [InventoryDeskController::class, 'showItem'])->name('items.show');
    Route::post('categories', [InventoryDeskController::class, 'storeCategory'])->name('categories.store');
    Route::get('stores', [InventoryDeskController::class, 'stores'])->name('stores');
    Route::post('stores', [InventoryDeskController::class, 'storeStore'])->name('stores.store');
    Route::post('stores/{store}/locations', [InventoryDeskController::class, 'addLocation'])->name('stores.locations');
    Route::get('stock', [InventoryDeskController::class, 'stock'])->name('stock');
    Route::get('reports', [InventoryDeskController::class, 'reports'])->name('reports');

    Route::get('expiry', [InventoryStockController::class, 'expiry'])->name('expiry');
    Route::post('batches/{batch}/hold', [InventoryStockController::class, 'hold'])->name('batches.hold');
    Route::post('batches/{batch}/release', [InventoryStockController::class, 'release'])->name('batches.release');
    Route::get('batches/{batch}', [InventoryStockController::class, 'trace'])->name('batches.trace');
    Route::post('recalls', [InventoryStockController::class, 'recall'])->name('recalls.store');
    Route::get('adjustments', [InventoryStockController::class, 'adjustmentsIndex'])->name('adjustments');
    Route::post('adjustments', [InventoryStockController::class, 'requestAdjustment'])->name('adjustments.store');
    Route::post('adjustments/{adjustment}/decide', [InventoryStockController::class, 'decideAdjustment'])->name('adjustments.decide');
    Route::get('counts', [InventoryStockController::class, 'countsIndex'])->name('counts');
    Route::post('counts', [InventoryStockController::class, 'startCount'])->name('counts.start');
    Route::get('counts/{count}', [InventoryStockController::class, 'showCount'])->name('counts.show');
    Route::post('counts/{count}/enter', [InventoryStockController::class, 'enterCount'])->name('counts.enter');
    Route::post('counts/{count}/decide', [InventoryStockController::class, 'decideCount'])->name('counts.decide');

    Route::get('transfers', [InventoryMovementController::class, 'transfersIndex'])->name('transfers');
    Route::post('transfers', [InventoryMovementController::class, 'requestTransfer'])->name('transfers.store');
    Route::post('transfers/{transfer}/move', [InventoryMovementController::class, 'moveTransfer'])->name('transfers.move');
    Route::get('requests', [InventoryMovementController::class, 'requestsIndex'])->name('requests');
    Route::post('requests', [InventoryMovementController::class, 'submitRequest'])->name('requests.store');
    Route::post('requests/{inventoryRequest}/decide', [InventoryMovementController::class, 'decideRequest'])->name('requests.decide');
    Route::post('issues', [InventoryMovementController::class, 'issueDirect'])->name('issues.store');
    Route::post('issue-lines/{line}/return', [InventoryMovementController::class, 'returnIssued'])->name('issues.return');
    Route::get('consumption', [InventoryMovementController::class, 'consumptionIndex'])->name('consumption');
    Route::post('consumption', [InventoryMovementController::class, 'recordConsumption'])->name('consumption.store');
});

Route::prefix('procurement')->name('procurement.')->group(function () {
    Route::get('suppliers', [ProcurementSupplierController::class, 'index'])->name('suppliers');
    Route::post('suppliers', [ProcurementSupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers/{supplier}', [ProcurementSupplierController::class, 'show'])->name('suppliers.show');
    Route::post('suppliers/{supplier}/documents', [ProcurementSupplierController::class, 'document'])->name('suppliers.documents');
    Route::post('suppliers/{supplier}/status', [ProcurementSupplierController::class, 'status'])->name('suppliers.status');
    Route::post('suppliers/{supplier}/evaluate', [ProcurementSupplierController::class, 'evaluate'])->name('suppliers.evaluate');
    Route::post('suppliers/{supplier}/contracts', [ProcurementSupplierController::class, 'contract'])->name('suppliers.contracts');
    Route::get('budgets', [ProcurementSupplierController::class, 'budgets'])->name('budgets');
    Route::post('budgets', [ProcurementSupplierController::class, 'storeBudget'])->name('budgets.store');

    Route::get('requisitions', [ProcurementOrderController::class, 'requisitionsIndex'])->name('requisitions');
    Route::post('requisitions', [ProcurementOrderController::class, 'storeRequisition'])->name('requisitions.store');
    Route::post('requisitions/{requisition}/decide', [ProcurementOrderController::class, 'decideRequisition'])->name('requisitions.decide');
    Route::get('reorder', [ProcurementOrderController::class, 'reorderIndex'])->name('reorder');
    Route::post('reorder', [ProcurementOrderController::class, 'raiseFromReorder'])->name('reorder.raise');
    Route::get('rfqs', [ProcurementOrderController::class, 'rfqsIndex'])->name('rfqs');
    Route::post('rfqs', [ProcurementOrderController::class, 'storeRfq'])->name('rfqs.store');
    Route::get('rfqs/{rfq}', [ProcurementOrderController::class, 'showRfq'])->name('rfqs.show');
    Route::post('rfqs/{rfq}/quotations', [ProcurementOrderController::class, 'quote'])->name('rfqs.quote');
    Route::post('rfqs/{rfq}/award', [ProcurementOrderController::class, 'award'])->name('rfqs.award');
    Route::get('orders', [ProcurementOrderController::class, 'ordersIndex'])->name('orders');
    Route::post('orders', [ProcurementOrderController::class, 'storeOrder'])->name('orders.store');
    Route::get('orders/{order}', [ProcurementOrderController::class, 'showOrder'])->name('orders.show');
    Route::post('orders/{order}/move', [ProcurementOrderController::class, 'moveOrder'])->name('orders.move');

    Route::get('receipts', [ProcurementReceivingController::class, 'receiptsIndex'])->name('receipts');
    Route::post('orders/{order}/receive', [ProcurementReceivingController::class, 'receive'])->name('orders.receive');
    Route::get('receipts/{receipt}', [ProcurementReceivingController::class, 'showReceipt'])->name('receipts.show');
    Route::post('receipts/{receipt}/inspect', [ProcurementReceivingController::class, 'inspect'])->name('receipts.inspect');
    Route::get('invoices', [ProcurementReceivingController::class, 'invoicesIndex'])->name('invoices');
    Route::post('orders/{order}/invoices', [ProcurementReceivingController::class, 'recordInvoice'])->name('orders.invoices');
    Route::post('invoices/{invoice}/decide', [ProcurementReceivingController::class, 'decideInvoice'])->name('invoices.decide');
    Route::get('returns', [ProcurementReceivingController::class, 'returnsIndex'])->name('returns');
    Route::post('returns', [ProcurementReceivingController::class, 'storeReturn'])->name('returns.store');
    Route::post('returns/{return}/move', [ProcurementReceivingController::class, 'moveReturn'])->name('returns.move');
});
