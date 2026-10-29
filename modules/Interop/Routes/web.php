<?php

use Illuminate\Support\Facades\Route;
use Modules\Interop\Http\Controllers\Web\InteropMessageController;
use Modules\Interop\Http\Controllers\Web\InteropPartnerController;
use Modules\Interop\Http\Controllers\Web\InteropToolsController;

// Staff side of interoperability: /admin/interop/*.
Route::prefix('interop')->name('interop.')->group(function () {
    Route::get('/', [InteropMessageController::class, 'dashboard'])->name('dashboard');

    Route::get('partners', [InteropPartnerController::class, 'index'])->name('partners');
    Route::post('partners', [InteropPartnerController::class, 'store'])->name('partners.store');
    Route::get('partners/{partner}', [InteropPartnerController::class, 'show'])->name('partners.show');
    Route::post('partners/{partner}', [InteropPartnerController::class, 'update'])->name('partners.update');
    Route::post('partners/{partner}/credentials', [InteropPartnerController::class, 'credential'])->name('partners.credentials');
    Route::post('partners/{partner}/tokens', [InteropPartnerController::class, 'token'])->name('partners.tokens');
    Route::post('partners/{partner}/endpoints', [InteropPartnerController::class, 'endpoint'])->name('partners.endpoints');
    Route::post('partners/{partner}/endpoints/{endpoint}/toggle', [InteropPartnerController::class, 'toggleEndpoint'])->whereNumber('endpoint')->name('partners.endpoints.toggle');
    Route::post('partners/{partner}/routes', [InteropPartnerController::class, 'route'])->name('partners.routes');
    Route::post('partners/{partner}/test-send', [InteropPartnerController::class, 'testSend'])->name('partners.test-send');
    Route::get('id-maps', [InteropPartnerController::class, 'idmaps'])->name('idmaps');
    Route::post('id-maps', [InteropPartnerController::class, 'link'])->name('idmaps.link');

    Route::get('messages', [InteropMessageController::class, 'messages'])->name('messages');
    Route::get('messages/{message}', [InteropMessageController::class, 'message'])->name('messages.show');
    Route::post('messages/{message}', [InteropMessageController::class, 'act'])->name('messages.act');
    Route::get('results', [InteropMessageController::class, 'results'])->name('results');
    Route::post('results/{result}', [InteropMessageController::class, 'review'])->name('results.review');
    Route::get('access-log', [InteropMessageController::class, 'accessLog'])->name('access-log');

    Route::get('worklist', [InteropToolsController::class, 'worklist'])->name('worklist');
    Route::post('worklist/sync', [InteropToolsController::class, 'syncWorklist'])->name('worklist.sync');
    Route::get('national', [InteropToolsController::class, 'national'])->name('national');
    Route::post('national', [InteropToolsController::class, 'share'])->name('national.share');
    Route::get('terminology', [InteropToolsController::class, 'terminology'])->name('terminology');
    Route::get('settings', [InteropToolsController::class, 'settings'])->name('settings');
    Route::post('settings', [InteropToolsController::class, 'saveSettings'])->name('settings.save');
});
