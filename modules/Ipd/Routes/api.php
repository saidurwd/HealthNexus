<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureOrganizationAccess;
use Illuminate\Support\Facades\Route;
use Modules\Ipd\Http\Controllers\Api\V1\IpdAdmissionController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdBedController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdCatalogController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdDashboardController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdDischargeController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdLeaveController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdTransferController;

Route::get('/ipd/dashboard', [IpdDashboardController::class, 'index'])->middleware(EnsureOrganizationAccess::class);

Route::get('/ipd/wards', [IpdCatalogController::class, 'wards'])->middleware(EnsureOrganizationAccess::class);
Route::get('/ipd/rooms', [IpdCatalogController::class, 'rooms'])->middleware(EnsureOrganizationAccess::class);
Route::get('/ipd/beds', [IpdCatalogController::class, 'beds'])->middleware(EnsureOrganizationAccess::class);
Route::get('/ipd/beds/availability', [IpdCatalogController::class, 'bedAvailability'])->middleware(EnsureOrganizationAccess::class);

Route::post('/ipd/beds/{bed}/reserve', [IpdBedController::class, 'reserve'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/beds/{bed}/allocate', [IpdBedController::class, 'allocate'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/beds/{bed}/release', [IpdBedController::class, 'release'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/ipd/admission-requests', [IpdAdmissionController::class, 'requestsIndex'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests', [IpdAdmissionController::class, 'requestsStore'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/ipd/admission-requests/{admissionRequest}', [IpdAdmissionController::class, 'requestsShow'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests/{admissionRequest}/approve', [IpdAdmissionController::class, 'requestsApprove'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests/{admissionRequest}/reject', [IpdAdmissionController::class, 'requestsReject'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests/{admissionRequest}/cancel', [IpdAdmissionController::class, 'requestsCancel'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/ipd/admissions', [IpdAdmissionController::class, 'index'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admissions', [IpdAdmissionController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/ipd/admissions/{admission}', [IpdAdmissionController::class, 'show'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/ipd/admissions/{admission}/movements', [IpdAdmissionController::class, 'movements'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::post('/ipd/admissions/{admission}/transfers', [IpdTransferController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/transfers/{transfer}/approve', [IpdTransferController::class, 'approve'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/transfers/{transfer}/complete', [IpdTransferController::class, 'complete'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::post('/ipd/admissions/{admission}/discharge-request', [IpdDischargeController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/discharge-requests/{dischargeRequest}/clearance', [IpdDischargeController::class, 'clearance'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/discharge-requests/{dischargeRequest}/complete', [IpdDischargeController::class, 'complete'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);

Route::post('/ipd/admissions/{admission}/leave', [IpdLeaveController::class, 'store'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/leaves/{leave}/return', [IpdLeaveController::class, 'markReturned'])->middleware(EnsureOrganizationAccess::class)->middleware(EnsureBranchAccess::class);
