<?php

use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;
use Modules\Rbac\Http\Controllers\PermissionController;
use Modules\Rbac\Http\Controllers\RoleController;
use Modules\Rbac\Http\Controllers\UserController;

Route::middleware('can:manage companies')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::prefix('companies/{company}')->name('companies.')->middleware(EnsureCompanyAccess::class)->group(function () {
        Route::get('users', [UserController::class, 'companyIndex'])->name('users.company-index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.company-show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.company-edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.company-update');
    });

    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);
});
