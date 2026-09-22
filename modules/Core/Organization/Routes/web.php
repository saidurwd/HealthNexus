<?php

use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;
use Modules\Core\Organization\Http\Controllers\BranchController;
use Modules\Core\Organization\Http\Controllers\CompanyController;
use Modules\Core\Organization\Http\Controllers\DepartmentController;

Route::middleware('can:manage companies')->group(function () {
    Route::resource('companies', CompanyController::class);

    Route::prefix('companies/{company}')->name('companies.')->middleware(EnsureCompanyAccess::class)->group(function () {
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

        Route::prefix('branches/{branch}')->name('branches.')->middleware(EnsureBranchAccess::class)->group(function () {
            Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
            Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
            Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
            Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
            Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        });
    });

    Route::get('departments', [DepartmentController::class, 'globalIndex'])->name('departments.global');
    Route::get('departments/create', [DepartmentController::class, 'createGlobal'])->name('departments.create.global');
});
