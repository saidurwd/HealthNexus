<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\Api\NotificationController;

Route::middleware(['auth:sanctum', 'can:notification.view'])->group(function () {
    Route::get('/notifications', NotificationController::class);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});
