<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\Web\BillingAdjustmentController;
use Modules\Billing\Http\Controllers\Web\BillingCashierSessionController;
use Modules\Billing\Http\Controllers\Web\BillingCategoryController;
use Modules\Billing\Http\Controllers\Web\BillingChargeController;
use Modules\Billing\Http\Controllers\Web\BillingCorporateController;
use Modules\Billing\Http\Controllers\Web\BillingDashboardController;
use Modules\Billing\Http\Controllers\Web\BillingInsuranceController;
use Modules\Billing\Http\Controllers\Web\BillingInvoiceController;
use Modules\Billing\Http\Controllers\Web\BillingItemController;
use Modules\Billing\Http\Controllers\Web\BillingPaymentController;
use Modules\Billing\Http\Controllers\Web\BillingPriceListController;
use Modules\Billing\Http\Controllers\Web\BillingReceiptController;
use Modules\Billing\Http\Controllers\Web\BillingRefundController;
use Modules\Billing\Http\Controllers\Web\BillingReportController;
use Modules\Billing\Http\Controllers\Web\BillingSettingsController;

Route::middleware(['can:billing.dashboard.view'])->group(function () {
    Route::get('billing', [BillingDashboardController::class, 'index'])->name('billing.dashboard');
});

// Pricing / catalog
Route::prefix('billing')->name('billing.')->group(function () {
    Route::middleware(['can:billing.pricing.view'])->group(function () {
        Route::get('categories', [BillingCategoryController::class, 'index'])->name('categories.index');
        Route::get('items', [BillingItemController::class, 'index'])->name('items.index');
        Route::get('price-lists', [BillingPriceListController::class, 'index'])->name('price-lists.index');
    });

    Route::middleware(['can:billing.category.manage'])->group(function () {
        Route::get('categories/create', [BillingCategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [BillingCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [BillingCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [BillingCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [BillingCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::middleware(['can:billing.item.manage'])->group(function () {
        Route::get('items/create', [BillingItemController::class, 'create'])->name('items.create');
        Route::post('items', [BillingItemController::class, 'store'])->name('items.store');
        Route::get('items/{item}/edit', [BillingItemController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [BillingItemController::class, 'update'])->name('items.update');
        Route::delete('items/{item}', [BillingItemController::class, 'destroy'])->name('items.destroy');
    });

    Route::middleware(['can:billing.pricing.manage'])->group(function () {
        Route::get('price-lists/create', [BillingPriceListController::class, 'create'])->name('price-lists.create');
        Route::post('price-lists', [BillingPriceListController::class, 'store'])->name('price-lists.store');
        Route::get('price-lists/{priceList}/edit', [BillingPriceListController::class, 'edit'])->name('price-lists.edit');
        Route::put('price-lists/{priceList}', [BillingPriceListController::class, 'update'])->name('price-lists.update');
        Route::post('price-lists/{priceList}/items', [BillingPriceListController::class, 'storeItem'])->name('price-lists.items.store');
        Route::delete('price-lists/{priceList}/items/{item}', [BillingPriceListController::class, 'destroyItem'])->name('price-lists.items.destroy');
    });

    // Wildcard "show" routes are registered last so they don't swallow literal sibling paths
    // like items/create or price-lists/create registered above ({item} would otherwise match
    // the literal string "create" first, since Laravel matches routes in registration order).
    Route::middleware(['can:billing.pricing.view'])->group(function () {
        Route::get('items/{item}', [BillingItemController::class, 'show'])->name('items.show');
        Route::get('price-lists/{priceList}', [BillingPriceListController::class, 'show'])->name('price-lists.show');
    });

    // Charges
    Route::middleware(['can:billing.charge.view'])->group(function () {
        Route::get('charges', [BillingChargeController::class, 'index'])->name('charges.index');
        Route::get('charges/{charge}', [BillingChargeController::class, 'show'])->name('charges.show');
    });
    Route::middleware(['can:billing.charge.cancel'])->group(function () {
        Route::post('charges/{charge}/cancel', [BillingChargeController::class, 'cancel'])->name('charges.cancel');
    });

    // Invoices — "create" must be registered before the wildcard "show" route below it, or
    // GET /invoices/create would match invoices/{invoice} with invoice="create" first.
    Route::middleware(['can:billing.invoice.view'])->group(function () {
        Route::get('invoices', [BillingInvoiceController::class, 'index'])->name('invoices.index');
    });
    Route::middleware(['can:billing.invoice.create'])->group(function () {
        Route::get('invoices/create', [BillingInvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [BillingInvoiceController::class, 'store'])->name('invoices.store');
    });
    Route::middleware(['can:billing.invoice.view'])->group(function () {
        Route::get('invoices/{invoice}', [BillingInvoiceController::class, 'show'])->name('invoices.show');
    });
    Route::middleware(['can:billing.invoice.update'])->group(function () {
        Route::post('invoices/{invoice}/charges', [BillingInvoiceController::class, 'addCharges'])->name('invoices.add-charges');
    });
    Route::middleware(['can:billing.invoice.finalize'])->group(function () {
        Route::post('invoices/{invoice}/finalize', [BillingInvoiceController::class, 'finalize'])->name('invoices.finalize');
    });
    Route::middleware(['can:billing.invoice.cancel'])->group(function () {
        Route::post('invoices/{invoice}/cancel', [BillingInvoiceController::class, 'cancel'])->name('invoices.cancel');
    });
    Route::middleware(['can:billing.invoice.writeoff'])->group(function () {
        Route::post('invoices/{invoice}/write-off', [BillingInvoiceController::class, 'writeOff'])->name('invoices.write-off');
    });

    // Payments — same ordering caveat as invoices above.
    Route::middleware(['can:billing.payment.view'])->group(function () {
        Route::get('payments', [BillingPaymentController::class, 'index'])->name('payments.index');
    });
    Route::middleware(['can:billing.payment.create'])->group(function () {
        Route::get('payments/create', [BillingPaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [BillingPaymentController::class, 'store'])->name('payments.store');
    });
    Route::middleware(['can:billing.payment.view'])->group(function () {
        Route::get('payments/{payment}', [BillingPaymentController::class, 'show'])->name('payments.show');
    });
    Route::middleware(['can:billing.payment.cancel'])->group(function () {
        Route::post('payments/{payment}/cancel', [BillingPaymentController::class, 'cancel'])->name('payments.cancel');
    });

    // Receipts
    Route::middleware(['can:billing.receipt.view'])->group(function () {
        Route::get('receipts', [BillingReceiptController::class, 'index'])->name('receipts.index');
        Route::get('receipts/{receipt}', [BillingReceiptController::class, 'show'])->name('receipts.show');
    });

    // Refunds
    Route::middleware(['can:billing.refund.request'])->group(function () {
        Route::get('refunds', [BillingRefundController::class, 'index'])->name('refunds.index');
        Route::get('refunds/create', [BillingRefundController::class, 'create'])->name('refunds.create');
        Route::post('refunds', [BillingRefundController::class, 'store'])->name('refunds.store');
        Route::get('refunds/{refund}', [BillingRefundController::class, 'show'])->name('refunds.show');
    });
    Route::middleware(['can:billing.refund.approve'])->group(function () {
        Route::post('refunds/{refund}/approve', [BillingRefundController::class, 'approve'])->name('refunds.approve');
    });
    Route::middleware(['can:billing.refund.process'])->group(function () {
        Route::post('refunds/{refund}/process', [BillingRefundController::class, 'process'])->name('refunds.process');
    });

    // Adjustments
    Route::middleware(['can:billing.adjustment.request'])->group(function () {
        Route::get('adjustments', [BillingAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('adjustments/create', [BillingAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('adjustments', [BillingAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::get('adjustments/{adjustment}', [BillingAdjustmentController::class, 'show'])->name('adjustments.show');
    });
    Route::middleware(['can:billing.adjustment.approve'])->group(function () {
        Route::post('adjustments/{adjustment}/approve', [BillingAdjustmentController::class, 'approve'])->name('adjustments.approve');
    });

    // Cashier
    Route::middleware(['can:billing.cashier.view'])->group(function () {
        Route::get('cashier', [BillingCashierSessionController::class, 'index'])->name('cashier.index');
        Route::get('cashier/my-session', [BillingCashierSessionController::class, 'mySession'])->name('cashier.my-session');
    });
    Route::middleware(['can:billing.cashier.open'])->group(function () {
        Route::get('cashier/open', [BillingCashierSessionController::class, 'create'])->name('cashier.open');
        Route::post('cashier/open', [BillingCashierSessionController::class, 'store'])->name('cashier.open.store');
    });
    Route::middleware(['can:billing.cashier.close'])->group(function () {
        Route::get('cashier/{session}/close', [BillingCashierSessionController::class, 'edit'])->name('cashier.close');
        Route::post('cashier/{session}/close', [BillingCashierSessionController::class, 'close'])->name('cashier.close.store');
    });
    Route::middleware(['can:billing.cashier.reconcile'])->group(function () {
        Route::get('cashier/{session}/reconciliation', [BillingCashierSessionController::class, 'reconciliation'])->name('cashier.reconciliation');
    });

    // Corporate — literal paths (index, receivables, create) must all be registered before the
    // wildcard "show" route below, or GET /corporates/create would match {corporate}="create".
    Route::middleware(['can:billing.corporate.view'])->group(function () {
        Route::get('corporates', [BillingCorporateController::class, 'index'])->name('corporates.index');
        Route::get('corporates/receivables', [BillingCorporateController::class, 'receivables'])->name('corporates.receivables');
    });
    Route::middleware(['can:billing.corporate.manage'])->group(function () {
        Route::get('corporates/create', [BillingCorporateController::class, 'create'])->name('corporates.create');
        Route::post('corporates', [BillingCorporateController::class, 'store'])->name('corporates.store');
        Route::get('corporates/{corporate}/edit', [BillingCorporateController::class, 'edit'])->name('corporates.edit');
        Route::put('corporates/{corporate}', [BillingCorporateController::class, 'update'])->name('corporates.update');
        Route::post('corporates/{corporate}/contracts', [BillingCorporateController::class, 'storeContract'])->name('corporates.contracts.store');
        Route::post('corporates/{corporate}/members', [BillingCorporateController::class, 'storeMember'])->name('corporates.members.store');
    });
    Route::middleware(['can:billing.corporate.view'])->group(function () {
        Route::get('corporates/{corporate}', [BillingCorporateController::class, 'show'])->name('corporates.show');
    });

    // Insurance
    Route::middleware(['can:insurance.view'])->group(function () {
        Route::get('insurance/providers', [BillingInsuranceController::class, 'providers'])->name('insurance.providers.index');
    });
    Route::middleware(['can:insurance.create'])->group(function () {
        Route::get('insurance/providers/create', [BillingInsuranceController::class, 'createProvider'])->name('insurance.providers.create');
        Route::post('insurance/providers', [BillingInsuranceController::class, 'storeProvider'])->name('insurance.providers.store');
    });
    Route::middleware(['can:insurance.update'])->group(function () {
        Route::get('insurance/providers/{provider}/edit', [BillingInsuranceController::class, 'editProvider'])->name('insurance.providers.edit');
        Route::put('insurance/providers/{provider}', [BillingInsuranceController::class, 'updateProvider'])->name('insurance.providers.update');
    });
    Route::middleware(['can:billing.insurance.policy.view'])->group(function () {
        Route::get('insurance/policies', [BillingInsuranceController::class, 'policies'])->name('insurance.policies.index');
    });
    Route::middleware(['can:billing.insurance.policy.manage'])->group(function () {
        Route::get('insurance/policies/create', [BillingInsuranceController::class, 'createPolicy'])->name('insurance.policies.create');
        Route::post('insurance/policies', [BillingInsuranceController::class, 'storePolicy'])->name('insurance.policies.store');
        Route::get('insurance/policies/{policy}/edit', [BillingInsuranceController::class, 'editPolicy'])->name('insurance.policies.edit');
        Route::put('insurance/policies/{policy}', [BillingInsuranceController::class, 'updatePolicy'])->name('insurance.policies.update');
    });

    // Reports
    Route::middleware(['can:billing.report.view'])->group(function () {
        Route::get('reports/billing', [BillingReportController::class, 'billing'])->name('reports.billing');
        Route::get('reports/collection', [BillingReportController::class, 'collection'])->name('reports.collection');
        Route::get('reports/revenue', [BillingReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('reports/receivables', [BillingReportController::class, 'receivables'])->name('reports.receivables');
        Route::get('reports/refunds', [BillingReportController::class, 'refunds'])->name('reports.refunds');
        Route::get('reports/discounts', [BillingReportController::class, 'discounts'])->name('reports.discounts');
    });

    // Settings
    Route::middleware(['can:billing.settings.manage'])->group(function () {
        Route::get('settings', [BillingSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [BillingSettingsController::class, 'update'])->name('settings.update');
    });
});
