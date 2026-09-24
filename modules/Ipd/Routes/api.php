<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;
use Modules\Ipd\Http\Controllers\Api\V1\IpdAdmissionController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdBedController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdCatalogController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdDashboardController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdDischargeController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdLeaveController;
use Modules\Ipd\Http\Controllers\Api\V1\IpdTransferController;

Route::get('/ipd/dashboard', [IpdDashboardController::class, 'index'])->middleware(EnsureCompanyAccess::class);

Route::get('/ipd/wards', [IpdCatalogController::class, 'wards'])->middleware(EnsureCompanyAccess::class);
Route::get('/ipd/rooms', [IpdCatalogController::class, 'rooms'])->middleware(EnsureCompanyAccess::class);
Route::get('/ipd/beds', [IpdCatalogController::class, 'beds'])->middleware(EnsureCompanyAccess::class);
Route::get('/ipd/beds/availability', [IpdCatalogController::class, 'bedAvailability'])->middleware(EnsureCompanyAccess::class);

Route::post('/ipd/beds/{bed}/reserve', [IpdBedController::class, 'reserve'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/beds/{bed}/allocate', [IpdBedController::class, 'allocate'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/beds/{bed}/release', [IpdBedController::class, 'release'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/ipd/admission-requests', [IpdAdmissionController::class, 'requestsIndex'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests', [IpdAdmissionController::class, 'requestsStore'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/ipd/admission-requests/{admissionRequest}', [IpdAdmissionController::class, 'requestsShow'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests/{admissionRequest}/approve', [IpdAdmissionController::class, 'requestsApprove'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests/{admissionRequest}/reject', [IpdAdmissionController::class, 'requestsReject'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admission-requests/{admissionRequest}/cancel', [IpdAdmissionController::class, 'requestsCancel'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/ipd/admissions', [IpdAdmissionController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/admissions', [IpdAdmissionController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/ipd/admissions/{admission}', [IpdAdmissionController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/ipd/admissions/{admission}/movements', [IpdAdmissionController::class, 'movements'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::post('/ipd/admissions/{admission}/transfers', [IpdTransferController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/transfers/{transfer}/approve', [IpdTransferController::class, 'approve'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/transfers/{transfer}/complete', [IpdTransferController::class, 'complete'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::post('/ipd/admissions/{admission}/discharge-request', [IpdDischargeController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/discharge-requests/{dischargeRequest}/clearance', [IpdDischargeController::class, 'clearance'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/discharge-requests/{dischargeRequest}/complete', [IpdDischargeController::class, 'complete'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::post('/ipd/admissions/{admission}/leave', [IpdLeaveController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/ipd/leaves/{leave}/return', [IpdLeaveController::class, 'markReturned'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
