<?php

use Illuminate\Support\Facades\Route;
use Modules\Files\Http\Controllers\Web\FileController;

Route::middleware(['can:file.view'])->group(function () {
    Route::get('files', [FileController::class, 'index'])->name('files.index');
    Route::get('files/{file}', [FileController::class, 'show'])->name('files.show');
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy')->middleware('can:file.manage');
});
