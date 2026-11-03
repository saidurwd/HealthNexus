<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureOrganizationAccess;
use Illuminate\Support\Facades\Route;
use Modules\Pharmacy\Http\Controllers\Api\V1\PharmacyCatalogController;
use Modules\Pharmacy\Http\Controllers\Api\V1\PharmacyDispensingController;
use Modules\Pharmacy\Http\Controllers\Api\V1\PharmacyOrderController;
use Modules\Pharmacy\Http\Controllers\Api\V1\PharmacySafetyAlertController;
use Modules\Pharmacy\Http\Controllers\Api\V1\PharmacyStockController;
use Modules\Pharmacy\Http\Controllers\Api\V1\PharmacyTransferController;

Route::get('/pharmacy/medications', [PharmacyCatalogController::class, 'medications'])->middleware(EnsureOrganizationAccess::class);
Route::get('/pharmacy/generics', [PharmacyCatalogController::class, 'generics'])->middleware(EnsureOrganizationAccess::class);
Route::get('/pharmacy/brands', [PharmacyCatalogController::class, 'brands'])->middleware(EnsureOrganizationAccess::class);

Route::get('/pharmacy/orders', [PharmacyOrderController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/pharmacy/orders/{order}', [PharmacyOrderController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/orders/{order}/cancel', [PharmacyOrderController::class, 'cancel'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/orders/{order}/dispense', [PharmacyDispensingController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/pharmacy/dispensing', [PharmacyDispensingController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/pharmacy/dispensing/{dispensing}', [PharmacyDispensingController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/dispensing/{dispensing}/verify', [PharmacyDispensingController::class, 'verify'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/dispensing/{dispensing}/return', [PharmacyDispensingController::class, 'storeReturn'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/pharmacy/stock', [PharmacyStockController::class, 'stock'])->middleware(EnsureOrganizationAccess::class);
Route::get('/pharmacy/batches', [PharmacyStockController::class, 'batches'])->middleware(EnsureOrganizationAccess::class);

Route::get('/pharmacy/transfers', [PharmacyTransferController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/pharmacy/transfers/{transfer}', [PharmacyTransferController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/transfers', [PharmacyTransferController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/transfers/{transfer}/approve', [PharmacyTransferController::class, 'approve'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/transfers/{transfer}/dispatch', [PharmacyTransferController::class, 'dispatch'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/transfers/{transfer}/receive', [PharmacyTransferController::class, 'receive'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/pharmacy/safety-alerts', [PharmacySafetyAlertController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/pharmacy/safety-alerts/{alert}/override', [PharmacySafetyAlertController::class, 'override'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
