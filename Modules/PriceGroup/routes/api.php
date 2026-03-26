<?php

use Illuminate\Support\Facades\Route;
use Modules\PriceGroup\app\Http\Controllers\DiscountGroupController;
use Modules\PriceGroup\app\Http\Controllers\SellingPriceGroupController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('price-groups')->group(function () {
        Route::get('/', [SellingPriceGroupController::class, 'index'])->middleware('permission:view_price_group');
        Route::post('/', [SellingPriceGroupController::class, 'store'])->middleware('permission:create_price_group');
        Route::get('/{id}', [SellingPriceGroupController::class, 'show'])->middleware('permission:view_price_group');
        Route::put('/{id}', [SellingPriceGroupController::class, 'update'])->middleware('permission:update_price_group');
        Route::delete('/{id}', [SellingPriceGroupController::class, 'destroy'])->middleware('permission:delete_price_group');
        Route::patch('/{id}/toggle-status', [SellingPriceGroupController::class, 'toggleActive'])->middleware('permission:update_price_group');
    });

    Route::prefix('discount-groups')->group(function () {
        Route::get('/', [DiscountGroupController::class, 'index'])->middleware('permission:view_discount_group');
        Route::post('/', [DiscountGroupController::class, 'store'])->middleware('permission:create_discount_group');
        Route::get('/{id}', [DiscountGroupController::class, 'show'])->middleware('permission:view_discount_group');
        Route::put('/{id}', [DiscountGroupController::class, 'update'])->middleware('permission:update_discount_group');
        Route::delete('/{id}', [DiscountGroupController::class, 'destroy'])->middleware('permission:delete_discount_group');
        Route::patch('/{id}/toggle-status', [DiscountGroupController::class, 'toggleActive'])->middleware('permission:update_discount_group');
    });
});
