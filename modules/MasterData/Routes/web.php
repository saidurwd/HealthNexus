<?php

use Illuminate\Support\Facades\Route;
use Modules\MasterData\Http\Controllers\MasterDataController;

Route::middleware(['can:settings.view'])->group(function () {
    Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');

    Route::get('master-data/countries', [MasterDataController::class, 'countries'])->name('master-data.countries');
    Route::get('master-data/countries/create', [MasterDataController::class, 'createCountry'])->name('master-data.countries.create');
    Route::post('master-data/countries', [MasterDataController::class, 'storeCountry'])->name('master-data.countries.store');
    Route::get('master-data/countries/{country}/edit', [MasterDataController::class, 'editCountry'])->name('master-data.countries.edit');
    Route::put('master-data/countries/{country}', [MasterDataController::class, 'updateCountry'])->name('master-data.countries.update');
    Route::delete('master-data/countries/{country}', [MasterDataController::class, 'destroyCountry'])->name('master-data.countries.destroy');

    Route::get('master-data/states', [MasterDataController::class, 'allStates'])->name('master-data.all-states');
    Route::get('master-data/states/create', [MasterDataController::class, 'createState'])->name('master-data.states.create');
    Route::post('master-data/states', [MasterDataController::class, 'storeState'])->name('master-data.states.store');
    Route::get('master-data/states/{state}/edit', [MasterDataController::class, 'editState'])->name('master-data.states.edit');
    Route::put('master-data/states/{state}', [MasterDataController::class, 'updateState'])->name('master-data.states.update');
    Route::delete('master-data/states/{state}', [MasterDataController::class, 'destroyState'])->name('master-data.states.destroy');
    Route::get('master-data/countries/{country}/states', [MasterDataController::class, 'states'])->name('master-data.states.by-country');

    Route::get('master-data/currencies', [MasterDataController::class, 'currencies'])->name('master-data.currencies');
    Route::get('master-data/currencies/create', [MasterDataController::class, 'createCurrency'])->name('master-data.currencies.create');
    Route::post('master-data/currencies', [MasterDataController::class, 'storeCurrency'])->name('master-data.currencies.store');
    Route::get('master-data/currencies/{currency}/edit', [MasterDataController::class, 'editCurrency'])->name('master-data.currencies.edit');
    Route::put('master-data/currencies/{currency}', [MasterDataController::class, 'updateCurrency'])->name('master-data.currencies.update');
    Route::delete('master-data/currencies/{currency}', [MasterDataController::class, 'destroyCurrency'])->name('master-data.currencies.destroy');

    Route::get('master-data/identification-types', [MasterDataController::class, 'identificationTypes'])->name('master-data.identification-types');
    Route::get('master-data/identification-types/create', [MasterDataController::class, 'createIdentificationType'])->name('master-data.identification-types.create');
    Route::post('master-data/identification-types', [MasterDataController::class, 'storeIdentificationType'])->name('master-data.identification-types.store');
    Route::get('master-data/identification-types/{identificationType}/edit', [MasterDataController::class, 'editIdentificationType'])->name('master-data.identification-types.edit');
    Route::put('master-data/identification-types/{identificationType}', [MasterDataController::class, 'updateIdentificationType'])->name('master-data.identification-types.update');
    Route::delete('master-data/identification-types/{identificationType}', [MasterDataController::class, 'destroyIdentificationType'])->name('master-data.identification-types.destroy');
});
