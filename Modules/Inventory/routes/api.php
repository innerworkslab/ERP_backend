<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\app\Http\Controllers\UnitOfMeasurementController;
use Modules\Inventory\app\Http\Controllers\UnitOfMeasurementConversionController;
use Modules\Inventory\app\Http\Controllers\InventoryController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/unit-of-measurements')->group(function () {
        Route::patch('{id}/toggle-status', [UnitOfMeasurementController::class, 'toggleActive'])->middleware('permission:update_uom');
        Route::get('/all', [UnitOfMeasurementController::class, 'index'])->middleware('permission:view_uom');
        Route::post('', [UnitOfMeasurementController::class, 'create'])->middleware('permission:create_uom');
        Route::put('{id}', [UnitOfMeasurementController::class, 'update'])->middleware('permission:update_uom');
        Route::get('{id}', [UnitOfMeasurementController::class, 'findOrFail'])->middleware('permission:view_uom');
    });

    Route::prefix('v1/uom-conversions')->group(function () {
        Route::patch('{id}/toggle-status', [UnitOfMeasurementConversionController::class, 'toggleActive'])->middleware('permission:update_uom_conversion');
        Route::get('/all', [UnitOfMeasurementConversionController::class, 'index'])->middleware('permission:view_uom_conversion');
        Route::post('', [UnitOfMeasurementConversionController::class, 'create'])->middleware('permission:create_uom_conversion');
        Route::put('{id}', [UnitOfMeasurementConversionController::class, 'update'])->middleware('permission:update_uom_conversion');
        Route::get('{id}', [UnitOfMeasurementConversionController::class, 'findOrFail'])->middleware('permission:view_uom_conversion');
    });

    Route::prefix('v1/inventories')->group(function(){
        Route::patch('{id}/branch/{branchId}/toggle-status', [InventoryController::class, 'toggleActive'])->middleware('permission:update_inventory');
        Route::get('', [InventoryController::class, 'index'])->middleware('permission:view_inventory');
        Route::post('', [InventoryController::class, 'create'])->middleware('permission:create_inventory'); 
        Route::put('{id}', [InventoryController::class, 'update'])->middleware('permission:update_inventory'); 
        Route::delete('{id}', [InventoryController::class, 'delete'])->middleware('permission:delete_inventory');
        Route::get('{id}', [InventoryController::class, 'findOrFail'])->middleware('permission:view_inventory');  
    });
});
