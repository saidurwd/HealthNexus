<?php

use App\Http\Middleware\EnsureOrganizationAccess;
use Illuminate\Support\Facades\Route;
use Modules\Rbac\Http\Controllers\PermissionController;
use Modules\Rbac\Http\Controllers\RoleController;
use Modules\Rbac\Http\Controllers\UserController;

Route::middleware('can:manage organizations')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::prefix('organizations/{organization}')->name('organizations.')->middleware(EnsureOrganizationAccess::class)->group(function () {
        Route::get('users', [UserController::class, 'organizationIndex'])->name('users.organization-index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.organization-show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.organization-edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.organization-update');
    });

    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('permissions', PermissionController::class)->except(['show']);
});
