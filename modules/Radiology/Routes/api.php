<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyCatalogController;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyCriticalFindingController;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyExaminationController;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyOrderController;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyReportController;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyStudyController;
use Modules\Radiology\Http\Controllers\Api\V1\RadiologyWorklistController;

Route::get('/radiology/procedures', [RadiologyCatalogController::class, 'procedures'])->middleware(EnsureCompanyAccess::class);
Route::get('/radiology/modalities', [RadiologyCatalogController::class, 'modalities'])->middleware(EnsureCompanyAccess::class);

Route::get('/radiology/orders', [RadiologyOrderController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/radiology/orders/{order}', [RadiologyOrderController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/orders/{order}/cancel', [RadiologyOrderController::class, 'cancel'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/radiology/examinations', [RadiologyExaminationController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/radiology/examinations/{examination}', [RadiologyExaminationController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/examinations/{examination}/start', [RadiologyExaminationController::class, 'start'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/examinations/{examination}/complete', [RadiologyExaminationController::class, 'complete'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/radiology/studies', [RadiologyStudyController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/radiology/studies/{study}', [RadiologyStudyController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/radiology/studies/{study}/viewer', [RadiologyStudyController::class, 'viewer'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/radiology/worklist', [RadiologyWorklistController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/radiology/reports', [RadiologyReportController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::get('/radiology/reports/{report}', [RadiologyReportController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/examinations/{examination}/report', [RadiologyReportController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/reports/{report}/submit', [RadiologyReportController::class, 'submit'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/reports/{report}/approve', [RadiologyReportController::class, 'approve'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/reports/{report}/amend', [RadiologyReportController::class, 'amend'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

Route::get('/radiology/critical-findings', [RadiologyCriticalFindingController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/reports/{report}/critical-findings', [RadiologyCriticalFindingController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
Route::post('/radiology/critical-findings/{finding}/acknowledge', [RadiologyCriticalFindingController::class, 'acknowledge'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
