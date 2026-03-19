<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\app\Http\Controllers\StaffController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('staffs')->group(function () {
        Route::get('/', [StaffController::class, 'index']);
        Route::post('/', [StaffController::class, 'store']);
        Route::get('/{id}', [StaffController::class, 'show']);
        Route::post('/{id}', [StaffController::class, 'update']);
        Route::delete('/{id}', [StaffController::class, 'destroy']);
        Route::patch('/{id}/toggle-status', [StaffController::class, 'toggleActive']);
        Route::patch('/{id}/change-password', [StaffController::class, 'changePassword']);
    });
});
