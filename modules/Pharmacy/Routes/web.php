<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyBrandController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyControlledDrugController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyDashboardController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyDispensingController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyGenericController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyMedicationController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyOrderController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyPatientHistoryController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyQuarantineController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyRecallController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacySafetyAlertController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacySettingsController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyStockController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyStockCountController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyTransferController;

Route::middleware(['can:pharmacy.dashboard.view'])->group(function () {
    Route::get('pharmacy', [PharmacyDashboardController::class, 'index'])->name('pharmacy.dashboard');
});

Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
    // Medications / Generics / Brands
    Route::middleware(['can:pharmacy.medication.view'])->group(function () {
        Route::get('medications', [PharmacyMedicationController::class, 'index'])->name('medications.index');
    });

    Route::middleware(['can:pharmacy.medication.create'])->group(function () {
        Route::get('medications/create', [PharmacyMedicationController::class, 'create'])->name('medications.create');
        Route::post('medications', [PharmacyMedicationController::class, 'store'])->name('medications.store');
    });

    Route::middleware(['can:pharmacy.medication.update'])->group(function () {
        Route::get('medications/{medication}/edit', [PharmacyMedicationController::class, 'edit'])->name('medications.edit');
        Route::put('medications/{medication}', [PharmacyMedicationController::class, 'update'])->name('medications.update');
        Route::delete('medications/{medication}', [PharmacyMedicationController::class, 'destroy'])->name('medications.destroy');
    });

    Route::middleware(['can:pharmacy.generic.view'])->group(function () {
        Route::get('generics', [PharmacyGenericController::class, 'index'])->name('generics.index');
    });

    Route::middleware(['can:pharmacy.generic.create'])->group(function () {
        Route::get('generics/create', [PharmacyGenericController::class, 'create'])->name('generics.create');
        Route::post('generics', [PharmacyGenericController::class, 'store'])->name('generics.store');
    });

    Route::middleware(['can:pharmacy.generic.update'])->group(function () {
        Route::get('generics/{generic}/edit', [PharmacyGenericController::class, 'edit'])->name('generics.edit');
        Route::put('generics/{generic}', [PharmacyGenericController::class, 'update'])->name('generics.update');
    });

    Route::middleware(['can:pharmacy.brand.view'])->group(function () {
        Route::get('brands', [PharmacyBrandController::class, 'index'])->name('brands.index');
    });

    Route::middleware(['can:pharmacy.brand.create'])->group(function () {
        Route::get('brands/create', [PharmacyBrandController::class, 'create'])->name('brands.create');
        Route::post('brands', [PharmacyBrandController::class, 'store'])->name('brands.store');
    });

    Route::middleware(['can:pharmacy.brand.update'])->group(function () {
        Route::get('brands/{brand}/edit', [PharmacyBrandController::class, 'edit'])->name('brands.edit');
        Route::put('brands/{brand}', [PharmacyBrandController::class, 'update'])->name('brands.update');
    });

    // Orders (pharmacy prescription queue)
    Route::middleware(['can:pharmacy.prescription.view'])->group(function () {
        Route::get('orders', [PharmacyOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [PharmacyOrderController::class, 'show'])->name('orders.show');
    });

    Route::middleware(['can:pharmacy.prescription.review'])->group(function () {
        Route::post('orders/{order}/resolve-item', [PharmacyOrderController::class, 'resolveUnmatchedItem'])->name('orders.resolve-item');
        Route::post('orders/{order}/cancel', [PharmacyOrderController::class, 'cancel'])->name('orders.cancel');
    });

    // Dispensing
    Route::middleware(['can:pharmacy.dispensing.view'])->group(function () {
        Route::get('dispensing', [PharmacyDispensingController::class, 'index'])->name('dispensing.index');
        Route::get('dispensing/{dispensing}', [PharmacyDispensingController::class, 'show'])->name('dispensing.show');
    });

    Route::middleware(['can:pharmacy.dispensing.create'])->group(function () {
        Route::get('orders/{order}/dispense', [PharmacyDispensingController::class, 'create'])->name('dispensing.create');
        Route::post('orders/{order}/dispense', [PharmacyDispensingController::class, 'store'])->name('dispensing.store');
    });

    Route::middleware(['can:pharmacy.dispensing.verify'])->group(function () {
        Route::post('dispensing/{dispensing}/verify', [PharmacyDispensingController::class, 'verify'])->name('dispensing.verify');
    });

    Route::middleware(['can:pharmacy.dispensing.return'])->group(function () {
        Route::post('dispensing/{dispensing}/return', [PharmacyDispensingController::class, 'storeReturn'])->name('dispensing.return');
    });

    // Stock / Batches
    Route::middleware(['can:pharmacy.stock.view'])->group(function () {
        Route::get('stock', [PharmacyStockController::class, 'index'])->name('stock.index');
    });

    Route::middleware(['can:pharmacy.batch.view'])->group(function () {
        Route::get('batches', [PharmacyStockController::class, 'batches'])->name('batches.index');
    });

    Route::middleware(['can:pharmacy.stock.receive'])->group(function () {
        Route::get('stock/receive', [PharmacyStockController::class, 'receiveForm'])->name('stock.receive-form');
        Route::post('stock/receive', [PharmacyStockController::class, 'receive'])->name('stock.receive');
    });

    Route::middleware(['can:pharmacy.stock.adjust'])->group(function () {
        Route::post('stock/adjust', [PharmacyStockController::class, 'adjust'])->name('stock.adjust');
    });

    // Transfers
    Route::middleware(['can:pharmacy.stock.transfer'])->group(function () {
        Route::get('transfers', [PharmacyTransferController::class, 'index'])->name('transfers.index');
        Route::get('transfers/create', [PharmacyTransferController::class, 'create'])->name('transfers.create');
        Route::post('transfers', [PharmacyTransferController::class, 'store'])->name('transfers.store');
        Route::get('transfers/{transfer}', [PharmacyTransferController::class, 'show'])->name('transfers.show');
        Route::post('transfers/{transfer}/approve', [PharmacyTransferController::class, 'approve'])->name('transfers.approve');
        Route::post('transfers/{transfer}/dispatch', [PharmacyTransferController::class, 'dispatch'])->name('transfers.dispatch');
        Route::post('transfers/{transfer}/receive', [PharmacyTransferController::class, 'receive'])->name('transfers.receive');
    });

    // Stock counts
    Route::middleware(['can:pharmacy.stock.count'])->group(function () {
        Route::get('stock-counts', [PharmacyStockCountController::class, 'index'])->name('stock-count.index');
        Route::post('stock-counts', [PharmacyStockCountController::class, 'start'])->name('stock-count.start');
        Route::get('stock-counts/{stockCount}', [PharmacyStockCountController::class, 'show'])->name('stock-count.show');
        Route::post('stock-counts/{stockCount}/record', [PharmacyStockCountController::class, 'recordCount'])->name('stock-count.record');
        Route::post('stock-counts/{stockCount}/complete', [PharmacyStockCountController::class, 'complete'])->name('stock-count.complete');
    });

    Route::middleware(['can:pharmacy.stock.adjust'])->group(function () {
        Route::post('stock-counts/{stockCount}/apply-adjustments', [PharmacyStockCountController::class, 'applyAdjustments'])->name('stock-count.apply-adjustments');
    });

    // Quarantine
    Route::middleware(['can:pharmacy.quarantine.manage'])->group(function () {
        Route::get('quarantine', [PharmacyQuarantineController::class, 'index'])->name('quarantine.index');
        Route::post('quarantine', [PharmacyQuarantineController::class, 'store'])->name('quarantine.store');
        Route::post('quarantine/{quarantine}/release', [PharmacyQuarantineController::class, 'release'])->name('quarantine.release');
        Route::post('quarantine/{quarantine}/dispose', [PharmacyQuarantineController::class, 'dispose'])->name('quarantine.dispose');
    });

    // Recalls
    Route::middleware(['can:pharmacy.recall.view'])->group(function () {
        Route::get('recalls', [PharmacyRecallController::class, 'index'])->name('recalls.index');
        Route::get('recalls/{recall}', [PharmacyRecallController::class, 'show'])->name('recalls.show');
    });

    Route::middleware(['can:pharmacy.recall.manage'])->group(function () {
        Route::post('recalls', [PharmacyRecallController::class, 'store'])->name('recalls.store');
        Route::post('recalls/{recall}/close', [PharmacyRecallController::class, 'close'])->name('recalls.close');
    });

    // Safety alerts
    Route::middleware(['can:pharmacy.safety_alert.view'])->group(function () {
        Route::get('safety-alerts', [PharmacySafetyAlertController::class, 'index'])->name('safety-alerts.index');
    });

    Route::middleware(['can:pharmacy.safety_alert.override'])->group(function () {
        Route::post('safety-alerts/{alert}/override', [PharmacySafetyAlertController::class, 'override'])->name('safety-alerts.override');
    });

    // Controlled drugs
    Route::middleware(['can:pharmacy.controlled_drug.view'])->group(function () {
        Route::get('controlled-drugs', [PharmacyControlledDrugController::class, 'index'])->name('controlled-drugs.index');
    });

    // Settings
    Route::middleware(['can:pharmacy.settings.manage'])->group(function () {
        Route::get('settings', [PharmacySettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [PharmacySettingsController::class, 'update'])->name('settings.update');
    });
});

// Patient medication history — nested under patients per the plan's "Encounter/Patient integration"
Route::middleware(['can:pharmacy.prescription.view'])->group(function () {
    Route::get('patients/{patient}/pharmacy-history', [PharmacyPatientHistoryController::class, 'show'])->name('patients.pharmacy-history');
});
