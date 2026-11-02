<?php

use Illuminate\Support\Facades\Route;
use Modules\Facility\Http\Controllers\Web\AmbulanceController;
use Modules\Facility\Http\Controllers\Web\AssetController;
use Modules\Facility\Http\Controllers\Web\CssdController;
use Modules\Facility\Http\Controllers\Web\DietController;
use Modules\Facility\Http\Controllers\Web\FacilityHomeController;
use Modules\Facility\Http\Controllers\Web\HousekeepingController;
use Modules\Facility\Http\Controllers\Web\MortuaryController;
use Modules\Facility\Http\Controllers\Web\SuppliesController;
use Modules\Facility\Http\Controllers\Web\VisitorController;
use Modules\Facility\Http\Controllers\Web\WorkOrderController;

// Facility and support services: /admin/facility/*. Any staff member can report a broken device; the rest needs a facility right.
Route::prefix('facility')->name('facility.')->group(function () {
    Route::get('/', [FacilityHomeController::class, 'index'])->name('index');

    Route::get('assets', [AssetController::class, 'index'])->name('assets');
    Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
    Route::get('assets/{asset}/label', [AssetController::class, 'label'])->name('assets.label');
    Route::post('assets/{asset}/move', [AssetController::class, 'move'])->name('assets.move');
    Route::post('assets/{asset}/retire', [AssetController::class, 'retire'])->name('assets.retire');
    Route::post('assets/{asset}/plans', [AssetController::class, 'plan'])->name('assets.plans');
    Route::post('assets/{asset}/calibrations', [AssetController::class, 'calibrate'])->name('assets.calibrate');
    Route::post('assets/{asset}/breakdown', [AssetController::class, 'breakdown'])->name('assets.breakdown');
    Route::get('scan/{token}', [AssetController::class, 'scan'])->name('scan');

    Route::get('work-orders', [WorkOrderController::class, 'index'])->name('workorders');
    Route::post('work-orders', [WorkOrderController::class, 'store'])->name('workorders.store');
    Route::get('work-orders/{order}', [WorkOrderController::class, 'show'])->name('workorders.show');
    Route::post('work-orders/{order}/assign', [WorkOrderController::class, 'assign'])->name('workorders.assign');
    Route::post('work-orders/{order}/start', [WorkOrderController::class, 'start'])->name('workorders.start');
    Route::post('work-orders/{order}/wait', [WorkOrderController::class, 'wait'])->name('workorders.wait');
    Route::post('work-orders/{order}/parts', [WorkOrderController::class, 'part'])->name('workorders.part');
    Route::post('work-orders/{order}/complete', [WorkOrderController::class, 'complete'])->name('workorders.complete');
    Route::post('work-orders/{order}/verify', [WorkOrderController::class, 'verify'])->name('workorders.verify');
    Route::post('work-orders/{order}/cancel', [WorkOrderController::class, 'cancel'])->name('workorders.cancel');

    Route::get('housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping');
    Route::post('housekeeping', [HousekeepingController::class, 'request'])->name('housekeeping.request');
    Route::post('housekeeping/{task}/start', [HousekeepingController::class, 'start'])->name('housekeeping.start');
    Route::post('housekeeping/{task}/complete', [HousekeepingController::class, 'complete'])->name('housekeeping.complete');
    Route::post('housekeeping/{task}/inspect', [HousekeepingController::class, 'inspect'])->name('housekeeping.inspect');
    Route::post('housekeeping/{task}/cancel', [HousekeepingController::class, 'cancel'])->name('housekeeping.cancel');
    Route::get('cleaning-audits', [HousekeepingController::class, 'audits'])->name('audits');
    Route::post('cleaning-audits', [HousekeepingController::class, 'storeAudit'])->name('audits.store');

    Route::get('sterile-services', [CssdController::class, 'index'])->name('cssd');
    Route::post('sterile-services/sets', [CssdController::class, 'storeSet'])->name('cssd.sets');
    Route::post('sterile-services/batches', [CssdController::class, 'load'])->name('cssd.load');
    Route::get('sterile-services/batches/{batch}', [CssdController::class, 'show'])->name('cssd.show');
    Route::post('sterile-services/batches/{batch}/start', [CssdController::class, 'start'])->name('cssd.start');
    Route::post('sterile-services/batches/{batch}/finish', [CssdController::class, 'finish'])->name('cssd.finish');
    Route::post('sterile-services/batches/{batch}/release', [CssdController::class, 'release'])->name('cssd.release');
    Route::post('sterile-services/batches/{batch}/recall', [CssdController::class, 'recall'])->name('cssd.recall');
    Route::post('sterile-services/packs/{item}/issue', [CssdController::class, 'issue'])->whereNumber('item')->name('cssd.issue');
    Route::get('sterile-services/trace', [CssdController::class, 'trace'])->name('cssd.trace');

    Route::get('linen', [SuppliesController::class, 'linen'])->name('linen');
    Route::post('linen/types', [SuppliesController::class, 'linenType'])->name('linen.type');
    Route::post('linen/movements', [SuppliesController::class, 'linenMove'])->name('linen.move');
    Route::get('waste', [SuppliesController::class, 'waste'])->name('waste');
    Route::post('waste/records', [SuppliesController::class, 'wasteRecord'])->name('waste.record');
    Route::post('waste/manifests', [SuppliesController::class, 'manifest'])->name('waste.manifest');
    Route::post('waste/manifests/{manifest}/hand-over', [SuppliesController::class, 'handOver'])->name('waste.handover');
    Route::post('waste/manifests/{manifest}/confirm', [SuppliesController::class, 'confirm'])->name('waste.confirm');

    Route::get('diets', [DietController::class, 'index'])->name('diets');
    Route::post('diets/types', [DietController::class, 'type'])->name('diets.type');
    Route::post('diets/orders', [DietController::class, 'order'])->name('diets.order');
    Route::post('diets/orders/{order}/stop', [DietController::class, 'stop'])->name('diets.stop');
    Route::get('meals', [DietController::class, 'meals'])->name('meals');
    Route::post('meals/trays/{tray}', [DietController::class, 'serve'])->whereNumber('tray')->name('meals.serve');
    Route::post('meals/{list}/close', [DietController::class, 'closeList'])->name('meals.close');

    Route::get('ambulances', [AmbulanceController::class, 'index'])->name('ambulances');
    Route::post('ambulances', [AmbulanceController::class, 'store'])->name('ambulances.store');
    Route::post('ambulances/{ambulance}/maintenance', [AmbulanceController::class, 'maintenance'])->name('ambulances.maintenance');
    Route::post('ambulances/{ambulance}/fuel', [AmbulanceController::class, 'fuel'])->name('ambulances.fuel');
    Route::post('trips', [AmbulanceController::class, 'request'])->name('trips.request');
    Route::post('trips/{trip}/dispatch', [AmbulanceController::class, 'dispatch'])->name('trips.dispatch');
    Route::post('trips/{trip}/arrive', [AmbulanceController::class, 'arrive'])->name('trips.arrive');
    Route::post('trips/{trip}/complete', [AmbulanceController::class, 'complete'])->name('trips.complete');
    Route::post('trips/{trip}/cancel', [AmbulanceController::class, 'cancel'])->name('trips.cancel');

    Route::get('mortuary', [MortuaryController::class, 'index'])->name('mortuary');
    Route::post('mortuary/units', [MortuaryController::class, 'unit'])->name('mortuary.unit');
    Route::post('mortuary/find', [MortuaryController::class, 'findDeaths'])->name('mortuary.find');
    Route::post('mortuary/{case}/receive', [MortuaryController::class, 'receive'])->name('mortuary.receive');
    Route::post('mortuary/{case}/police', [MortuaryController::class, 'police'])->name('mortuary.police');
    Route::post('mortuary/{case}/release', [MortuaryController::class, 'requestRelease'])->name('mortuary.release');
    Route::post('mortuary/{case}/authorise', [MortuaryController::class, 'authorise'])->name('mortuary.authorise');
    Route::post('mortuary/{case}/cancel-release', [MortuaryController::class, 'cancelRelease'])->name('mortuary.cancelrelease');

    Route::get('visitors', [VisitorController::class, 'index'])->name('visitors');
    Route::post('visitors', [VisitorController::class, 'issue'])->name('visitors.issue');
    Route::get('gate', [VisitorController::class, 'gate'])->name('gate');
    Route::post('gate/enter', [VisitorController::class, 'enter'])->name('gate.enter');
    Route::post('gate/leave', [VisitorController::class, 'leave'])->name('gate.leave');
    Route::post('visitors/{pass}/revoke', [VisitorController::class, 'revoke'])->name('visitors.revoke');
    Route::post('visitor-blocks', [VisitorController::class, 'block'])->name('visitors.block');
    Route::post('visitor-blocks/{block}/lift', [VisitorController::class, 'unblock'])->name('visitors.unblock');
});
