<?php

use Illuminate\Support\Facades\Route;
use Modules\Ipd\Http\Controllers\Web\IpdAdmissionController;
use Modules\Ipd\Http\Controllers\Web\IpdAdmissionRequestController;
use Modules\Ipd\Http\Controllers\Web\IpdBedBoardController;
use Modules\Ipd\Http\Controllers\Web\IpdBedController;
use Modules\Ipd\Http\Controllers\Web\IpdDashboardController;
use Modules\Ipd\Http\Controllers\Web\IpdDischargeController;
use Modules\Ipd\Http\Controllers\Web\IpdLeaveController;
use Modules\Ipd\Http\Controllers\Web\IpdPatientHistoryController;
use Modules\Ipd\Http\Controllers\Web\IpdReportingController;
use Modules\Ipd\Http\Controllers\Web\IpdRoomController;
use Modules\Ipd\Http\Controllers\Web\IpdSettingsController;
use Modules\Ipd\Http\Controllers\Web\IpdTransferController;
use Modules\Ipd\Http\Controllers\Web\IpdWardController;

Route::middleware(['can:ipd.dashboard.view'])->group(function () {
    Route::get('ipd', [IpdDashboardController::class, 'index'])->name('ipd.dashboard');
});

Route::prefix('ipd')->name('ipd.')->group(function () {
    // Admission requests — /create must be registered before the /{admissionRequest} wildcard,
    // otherwise the router matches "create" as the id and 404s on the implicit model binding.
    Route::middleware(['can:ipd.admission.create'])->group(function () {
        Route::get('admission-requests/create', [IpdAdmissionRequestController::class, 'create'])->name('admission-requests.create');
        Route::post('admission-requests', [IpdAdmissionRequestController::class, 'store'])->name('admission-requests.store');
    });

    Route::middleware(['can:ipd.admission.view'])->group(function () {
        Route::get('admission-requests', [IpdAdmissionRequestController::class, 'index'])->name('admission-requests.index');
        Route::get('admission-requests/{admissionRequest}', [IpdAdmissionRequestController::class, 'show'])->name('admission-requests.show');
    });

    Route::middleware(['can:ipd.admission.approve'])->group(function () {
        Route::post('admission-requests/{admissionRequest}/approve', [IpdAdmissionRequestController::class, 'approve'])->name('admission-requests.approve');
        Route::post('admission-requests/{admissionRequest}/reject', [IpdAdmissionRequestController::class, 'reject'])->name('admission-requests.reject');
    });

    Route::middleware(['can:ipd.admission.cancel'])->group(function () {
        Route::post('admission-requests/{admissionRequest}/cancel', [IpdAdmissionRequestController::class, 'cancel'])->name('admission-requests.cancel');
    });

    // Admissions — same /create-before-/{admission} ordering requirement.
    Route::middleware(['can:ipd.admission.create'])->group(function () {
        Route::get('admissions/create', [IpdAdmissionController::class, 'create'])->name('admissions.create');
        Route::post('admissions', [IpdAdmissionController::class, 'store'])->name('admissions.store');
    });

    Route::middleware(['can:ipd.admission.view'])->group(function () {
        Route::get('admissions', [IpdAdmissionController::class, 'index'])->name('admissions.index');
        Route::get('admissions/{admission}', [IpdAdmissionController::class, 'show'])->name('admissions.show');
    });

    // Wards / Rooms / Beds
    Route::middleware(['can:ipd.ward.view'])->group(function () {
        Route::get('wards', [IpdWardController::class, 'index'])->name('wards.index');
    });
    Route::middleware(['can:ipd.ward.create'])->group(function () {
        Route::get('wards/create', [IpdWardController::class, 'create'])->name('wards.create');
        Route::post('wards', [IpdWardController::class, 'store'])->name('wards.store');
    });
    Route::middleware(['can:ipd.ward.update'])->group(function () {
        Route::get('wards/{ward}/edit', [IpdWardController::class, 'edit'])->name('wards.edit');
        Route::put('wards/{ward}', [IpdWardController::class, 'update'])->name('wards.update');
    });

    Route::middleware(['can:ipd.room.view'])->group(function () {
        Route::get('rooms', [IpdRoomController::class, 'index'])->name('rooms.index');
    });
    Route::middleware(['can:ipd.room.create'])->group(function () {
        Route::get('rooms/create', [IpdRoomController::class, 'create'])->name('rooms.create');
        Route::post('rooms', [IpdRoomController::class, 'store'])->name('rooms.store');
    });
    Route::middleware(['can:ipd.room.update'])->group(function () {
        Route::get('rooms/{room}/edit', [IpdRoomController::class, 'edit'])->name('rooms.edit');
        Route::put('rooms/{room}', [IpdRoomController::class, 'update'])->name('rooms.update');
    });

    Route::middleware(['can:ipd.bed.view'])->group(function () {
        Route::get('beds', [IpdBedController::class, 'index'])->name('beds.index');
        Route::get('bed-board', [IpdBedBoardController::class, 'index'])->name('bed-board.index');
        Route::get('bed-availability', [IpdBedBoardController::class, 'availability'])->name('bed-availability.index');
    });
    Route::middleware(['can:ipd.bed.create'])->group(function () {
        Route::get('beds/create', [IpdBedController::class, 'create'])->name('beds.create');
        Route::post('beds', [IpdBedController::class, 'store'])->name('beds.store');
    });
    Route::middleware(['can:ipd.bed.update'])->group(function () {
        Route::get('beds/{bed}/edit', [IpdBedController::class, 'edit'])->name('beds.edit');
        Route::put('beds/{bed}', [IpdBedController::class, 'update'])->name('beds.update');
    });
    Route::middleware(['can:ipd.bed.block'])->group(function () {
        Route::post('beds/{bed}/block', [IpdBedController::class, 'block'])->name('beds.block');
    });
    Route::middleware(['can:ipd.bed.unblock'])->group(function () {
        Route::post('bed-blocks/{block}/unblock', [IpdBedController::class, 'unblock'])->name('bed-blocks.unblock');
    });

    // Transfers
    Route::middleware(['can:ipd.transfer.view'])->group(function () {
        Route::get('transfers', [IpdTransferController::class, 'index'])->name('transfers.index');
        Route::get('transfers/{transfer}', [IpdTransferController::class, 'show'])->name('transfers.show');
    });
    Route::middleware(['can:ipd.transfer.create'])->group(function () {
        Route::get('admissions/{admission}/transfer', [IpdTransferController::class, 'create'])->name('transfers.create');
        Route::post('admissions/{admission}/transfer', [IpdTransferController::class, 'store'])->name('transfers.store');
    });
    Route::middleware(['can:ipd.transfer.approve'])->group(function () {
        Route::post('transfers/{transfer}/approve', [IpdTransferController::class, 'approve'])->name('transfers.approve');
    });
    Route::middleware(['can:ipd.transfer.complete'])->group(function () {
        Route::post('transfers/{transfer}/complete', [IpdTransferController::class, 'complete'])->name('transfers.complete');
    });
    Route::middleware(['can:ipd.transfer.cancel'])->group(function () {
        Route::post('transfers/{transfer}/cancel', [IpdTransferController::class, 'cancel'])->name('transfers.cancel');
    });

    // Discharge
    Route::middleware(['can:ipd.discharge.view'])->group(function () {
        Route::get('discharge', [IpdDischargeController::class, 'index'])->name('discharge.index');
        Route::get('discharge/{dischargeRequest}', [IpdDischargeController::class, 'show'])->name('discharge.show');
    });
    Route::middleware(['can:ipd.discharge.create'])->group(function () {
        Route::get('admissions/{admission}/discharge', [IpdDischargeController::class, 'create'])->name('discharge.create');
        Route::post('admissions/{admission}/discharge', [IpdDischargeController::class, 'store'])->name('discharge.store');
    });
    Route::middleware(['can:ipd.discharge.approve'])->group(function () {
        Route::post('discharge/{dischargeRequest}/clearance', [IpdDischargeController::class, 'recordClearance'])->name('discharge.clearance');
    });
    Route::middleware(['can:ipd.discharge.complete'])->group(function () {
        Route::post('discharge/{dischargeRequest}/complete', [IpdDischargeController::class, 'complete'])->name('discharge.complete');
    });

    // Leave
    Route::middleware(['can:ipd.leave.view'])->group(function () {
        Route::get('leave', [IpdLeaveController::class, 'index'])->name('leave.index');
        Route::get('leave/{leave}', [IpdLeaveController::class, 'show'])->name('leave.show');
    });
    Route::middleware(['can:ipd.leave.create'])->group(function () {
        Route::post('admissions/{admission}/leave', [IpdLeaveController::class, 'store'])->name('leave.store');
    });
    Route::middleware(['can:ipd.leave.approve'])->group(function () {
        Route::post('leave/{leave}/approve', [IpdLeaveController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leave}/start', [IpdLeaveController::class, 'start'])->name('leave.start');
        Route::post('leave/{leave}/cancel', [IpdLeaveController::class, 'cancel'])->name('leave.cancel');
    });
    Route::middleware(['can:ipd.leave.complete'])->group(function () {
        Route::post('leave/{leave}/return', [IpdLeaveController::class, 'markReturned'])->name('leave.return');
    });

    // Reports
    Route::middleware(['can:ipd.reports.view'])->group(function () {
        Route::get('reports/occupancy', [IpdReportingController::class, 'occupancy'])->name('reports.occupancy');
        Route::get('reports/admissions', [IpdReportingController::class, 'admissions'])->name('reports.admissions');
        Route::get('reports/discharges', [IpdReportingController::class, 'discharges'])->name('reports.discharges');
        Route::get('reports/transfers', [IpdReportingController::class, 'transfers'])->name('reports.transfers');
    });

    // Settings
    Route::middleware(['can:ipd.settings.manage'])->group(function () {
        Route::get('settings', [IpdSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [IpdSettingsController::class, 'update'])->name('settings.update');
    });
});

// Patient IPD history — nested under patients per the plan's Patient 360 integration.
Route::middleware(['can:ipd.admission.view'])->group(function () {
    Route::get('patients/{patient}/ipd-history', [IpdPatientHistoryController::class, 'show'])->name('patients.ipd-history');
});
