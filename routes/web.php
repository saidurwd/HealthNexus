<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureCompanyAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('home');

    Route::prefix('admin')->name('admin.')->middleware('can:manage companies')->group(function () {
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

        Route::prefix('companies/{company}')->name('companies.')->middleware(EnsureCompanyAccess::class)->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        });

        Route::get('users', [UserController::class, 'globalIndex'])->name('users.global');

        Route::get('departments', [DepartmentController::class, 'globalIndex'])->name('departments.global');

        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);
    });
});
