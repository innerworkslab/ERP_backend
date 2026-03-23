<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\app\Http\Controllers\VariationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/variations')->group(function () {
        Route::patch('{id}/toggle-status', [VariationController::class, 'toggleActive'])->middleware('permission:update_variation');
        Route::get('/all', [VariationController::class, 'index'])->middleware('permission:view_variation');
        Route::post('', [VariationController::class, 'create'])->middleware('permission:create_variation');
        Route::put('{id}', [VariationController::class, 'update'])->middleware('permission:update_variation');
        Route::get('{id}', [VariationController::class, 'findOrFail'])->middleware('permission:view_variation');
    });
});
