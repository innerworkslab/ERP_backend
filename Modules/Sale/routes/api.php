<?php

use Illuminate\Support\Facades\Route;
use Modules\Sale\app\Http\Controllers\DeliveryProviderController;
// use Modules\Sale\Http\Controllers\SaleController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Route::apiResource('sales', SaleController::class)->names('sale');

    // Delivery Provider
    Route::prefix('delivery-providers')->group(function () {
        Route::patch('{id}/toggle-status', [DeliveryProviderController::class, 'toggleActive'])->middleware('permission:update_delivery_provider');
        Route::get('', [DeliveryProviderController::class, 'index'])->middleware('permission:view_delivery_provider');
        Route::post('', [DeliveryProviderController::class, 'create'])->middleware('permission:create_delivery_provider');
        Route::put('{id}', [DeliveryProviderController::class, 'update'])->middleware('permission:update_delivery_provider');
        Route::get('{id}', [DeliveryProviderController::class, 'findOrFail'])->middleware('permission:view_delivery_provider');
    });
});
