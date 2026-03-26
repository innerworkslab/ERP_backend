<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\app\Http\Controllers\BrandController;
use Modules\Product\app\Http\Controllers\VariationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/variations')->group(function () {
        Route::patch('{id}/toggle-status', [VariationController::class, 'toggleActive'])->middleware('permission:update_variation');
        Route::get('/all', [VariationController::class, 'index'])->middleware('permission:view_variation');
        Route::post('', [VariationController::class, 'create'])->middleware('permission:create_variation');
        Route::put('{id}', [VariationController::class, 'update'])->middleware('permission:update_variation');
        Route::get('{id}', [VariationController::class, 'findOrFail'])->middleware('permission:view_variation');
    });

    Route::prefix('v1/brands')->group(function () {
        Route::patch('{id}/toggle-status', [BrandController::class, 'toggleActive'])->middleware('permission:update_brand');
        Route::get('', [BrandController::class, 'index'])->middleware('permission:view_brand');
        Route::post('', [BrandController::class, 'create'])->middleware('permission:create_brand');
        Route::put('{id}', [BrandController::class, 'update'])->middleware('permission:update_brand');
        Route::delete('{id}', [BrandController::class, 'delete'])->middleware('permission:delete_brand');
        Route::get('{id}', [BrandController::class, 'findOrFail'])->middleware('permission:view_brand');
    });
});
