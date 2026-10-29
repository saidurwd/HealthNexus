<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Web\FinanceAssetController;
use Modules\Finance\Http\Controllers\Web\FinanceBankController;
use Modules\Finance\Http\Controllers\Web\FinanceBudgetController;
use Modules\Finance\Http\Controllers\Web\FinanceDashboardController;
use Modules\Finance\Http\Controllers\Web\FinanceJournalController;
use Modules\Finance\Http\Controllers\Web\FinancePayableController;
use Modules\Finance\Http\Controllers\Web\FinanceReceivableController;
use Modules\Finance\Http\Controllers\Web\FinanceReportController;
use Modules\Finance\Http\Controllers\Web\FinanceSetupController;
use Modules\Finance\Http\Controllers\Web\FinanceTaxController;

// Finance and accounting: /admin/finance/*.
Route::prefix('finance')->name('finance.')->group(function () {
    Route::get('/', [FinanceDashboardController::class, 'index'])->name('dashboard');
    Route::get('postings', [FinanceDashboardController::class, 'postings'])->name('postings');
    Route::post('postings/retry', [FinanceDashboardController::class, 'retry'])->name('postings.retry');
    Route::get('reconcile', [FinanceDashboardController::class, 'reconcile'])->name('reconcile');

    // set-up
    Route::get('accounts', [FinanceSetupController::class, 'accounts'])->name('accounts');
    Route::post('accounts/start', [FinanceSetupController::class, 'startBooks'])->name('accounts.start');
    Route::post('accounts', [FinanceSetupController::class, 'storeAccount'])->name('accounts.store');
    Route::post('accounts/{account}', [FinanceSetupController::class, 'updateAccount'])->name('accounts.update');
    Route::post('accounts/{account}/delete', [FinanceSetupController::class, 'deleteAccount'])->name('accounts.delete');
    Route::get('cost-centers', [FinanceSetupController::class, 'costCenters'])->name('cost-centers');
    Route::post('cost-centers', [FinanceSetupController::class, 'storeCostCenter'])->name('cost-centers.store');
    Route::get('roles', [FinanceSetupController::class, 'roles'])->name('roles');
    Route::post('roles', [FinanceSetupController::class, 'saveRole'])->name('roles.save');
    Route::get('years', [FinanceSetupController::class, 'years'])->name('years');
    Route::post('years', [FinanceSetupController::class, 'storeYear'])->name('years.store');
    Route::post('periods/{period}', [FinanceSetupController::class, 'lockPeriod'])->name('periods.lock');
    Route::post('years/{year}/close', [FinanceSetupController::class, 'closeYear'])->name('years.close');
    Route::get('settings', [FinanceSetupController::class, 'settings'])->name('settings');
    Route::post('settings', [FinanceSetupController::class, 'saveSettings'])->name('settings.save');

    // journals
    Route::get('journals', [FinanceJournalController::class, 'index'])->name('journals');
    Route::get('journals/new', [FinanceJournalController::class, 'create'])->name('journals.create');
    Route::post('journals', [FinanceJournalController::class, 'store'])->name('journals.store');
    Route::get('journals/{journal}', [FinanceJournalController::class, 'show'])->name('journals.show');
    Route::post('journals/{journal}', [FinanceJournalController::class, 'act'])->name('journals.act');

    // books and statements
    Route::get('trial-balance', [FinanceReportController::class, 'trialBalance'])->name('trial-balance');
    Route::get('ledger/{account}', [FinanceReportController::class, 'ledger'])->name('ledger');
    Route::get('income-statement', [FinanceReportController::class, 'incomeStatement'])->name('income-statement');
    Route::get('balance-sheet', [FinanceReportController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('cash-flow', [FinanceReportController::class, 'cashFlow'])->name('cash-flow');
    Route::get('profitability', [FinanceReportController::class, 'profitability'])->name('profitability');
    Route::get('doctors', [FinanceReportController::class, 'doctors'])->name('doctors');
    Route::post('doctors/rules', [FinanceReportController::class, 'saveRule'])->name('doctors.rules');
    Route::post('doctors/accrue/{period}', [FinanceReportController::class, 'accrue'])->name('doctors.accrue');

    // receivables and payables
    Route::get('receivables', [FinanceReceivableController::class, 'index'])->name('receivables');
    Route::get('receivables/{type}/{id}', [FinanceReceivableController::class, 'statement'])->whereNumber('id')->name('receivables.statement');
    Route::get('writeoffs', [FinanceReceivableController::class, 'writeoffs'])->name('writeoffs');
    Route::post('receivables/{receivable}/writeoff', [FinanceReceivableController::class, 'requestWriteoff'])->name('receivables.writeoff');
    Route::post('writeoffs/{writeoff}', [FinanceReceivableController::class, 'decide'])->name('writeoffs.decide');
    Route::get('payables', [FinancePayableController::class, 'index'])->name('payables');
    Route::get('payments', [FinancePayableController::class, 'payments'])->name('payments');
    Route::get('payments/new', [FinancePayableController::class, 'createPayment'])->name('payments.create');
    Route::post('payments', [FinancePayableController::class, 'storePayment'])->name('payments.store');
    Route::get('payments/{payment}', [FinancePayableController::class, 'showPayment'])->name('payments.show');
    Route::post('payments/{payment}', [FinancePayableController::class, 'act'])->name('payments.act');

    // cash and bank
    Route::get('banks', [FinanceBankController::class, 'accounts'])->name('banks');
    Route::post('banks', [FinanceBankController::class, 'storeAccount'])->name('banks.store');
    Route::get('deposits', [FinanceBankController::class, 'deposits'])->name('deposits');
    Route::post('deposits/session', [FinanceBankController::class, 'bankSession'])->name('deposits.session');
    Route::post('deposits/transfer', [FinanceBankController::class, 'transfer'])->name('deposits.transfer');
    Route::post('deposits/settlement', [FinanceBankController::class, 'settleGateway'])->name('deposits.settlement');
    Route::get('statements', [FinanceBankController::class, 'statements'])->name('statements');
    Route::post('statements', [FinanceBankController::class, 'importStatement'])->name('statements.import');
    Route::get('statements/{statement}', [FinanceBankController::class, 'showStatement'])->name('statements.show');
    Route::post('statements/{statement}', [FinanceBankController::class, 'actStatement'])->name('statements.act');

    // tax, budgets, assets
    Route::get('tax-codes', [FinanceTaxController::class, 'codes'])->name('tax-codes');
    Route::post('tax-codes', [FinanceTaxController::class, 'saveCode'])->name('tax-codes.save');
    Route::get('tax-register', [FinanceTaxController::class, 'register'])->name('tax-register');
    Route::get('tax-worksheet', [FinanceTaxController::class, 'worksheet'])->name('tax-worksheet');
    Route::get('budgets', [FinanceBudgetController::class, 'index'])->name('budgets');
    Route::post('budgets', [FinanceBudgetController::class, 'store'])->name('budgets.store');
    Route::post('budgets/check', [FinanceBudgetController::class, 'check'])->name('budgets.check');
    Route::get('budgets/{budget}', [FinanceBudgetController::class, 'show'])->name('budgets.show');
    Route::post('budgets/{budget}/lines', [FinanceBudgetController::class, 'setLine'])->name('budgets.lines');
    Route::post('budgets/{budget}/approve', [FinanceBudgetController::class, 'approve'])->name('budgets.approve');
    Route::get('assets', [FinanceAssetController::class, 'index'])->name('assets');
    Route::post('assets', [FinanceAssetController::class, 'store'])->name('assets.store');
    Route::post('assets/depreciate', [FinanceAssetController::class, 'depreciate'])->name('assets.depreciate');
    Route::post('assets/{asset}/dispose', [FinanceAssetController::class, 'dispose'])->name('assets.dispose');
});
