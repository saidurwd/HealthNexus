<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/tenant/context', [TenantContextController::class, 'show']);
        Route::post('/tenant/context', [TenantContextController::class, 'update']);

        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/companies/{company}', [CompanyController::class, 'show'])->middleware(EnsureCompanyAccess::class);
        Route::put('/companies/{company}', [CompanyController::class, 'update'])->middleware(EnsureCompanyAccess::class);
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->middleware(EnsureCompanyAccess::class);

        Route::get('/companies/{company}/branches', [BranchController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::post('/companies/{company}/branches', [BranchController::class, 'store'])->middleware(EnsureCompanyAccess::class);
        Route::get('/companies/{company}/branches/{branch}', [BranchController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/companies/{company}/branches/{branch}', [BranchController::class, 'update'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/companies/{company}/branches/{branch}', [BranchController::class, 'destroy'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/companies/{company}/branches/{branch}/departments', [DepartmentController::class, 'index'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::post('/companies/{company}/branches/{branch}/departments', [DepartmentController::class, 'store'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::get('/companies/{company}/branches/{branch}/departments/{department}', [DepartmentController::class, 'show'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::put('/companies/{company}/branches/{branch}/departments/{department}', [DepartmentController::class, 'update'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);
        Route::delete('/companies/{company}/branches/{branch}/departments/{department}', [DepartmentController::class, 'destroy'])->middleware(EnsureCompanyAccess::class)->middleware(EnsureBranchAccess::class);

        Route::get('/companies/{company}/users', [UserController::class, 'index'])->middleware(EnsureCompanyAccess::class);
        Route::get('/companies/{company}/users/{user}', [UserController::class, 'show'])->middleware(EnsureCompanyAccess::class);
        Route::put('/companies/{company}/users/{user}', [UserController::class, 'update'])->middleware(EnsureCompanyAccess::class);
    });
});
