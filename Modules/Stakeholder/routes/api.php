<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholder\app\Http\Controllers\Customer\CustomerController;
use Modules\Stakeholder\app\Http\Controllers\Supplier\SupplierController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    //Customer Routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->middleware('permission:view_customer');
        Route::post('/', [CustomerController::class, 'store'])->middleware('permission:create_customer');
        Route::get('/{id}', [CustomerController::class, 'show'])->middleware('permission:view_customer');
        Route::put('/{id}', [CustomerController::class, 'update'])->middleware('permission:update_customer');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('permission:delete_customer');
    });

    //Supplier Routes
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('permission:view_supplier');
        Route::post('/', [SupplierController::class, 'store'])->middleware('permission:create_supplier');
        Route::get('/{id}', [SupplierController::class, 'show'])->middleware('permission:view_supplier');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('permission:update_supplier');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('permission:delete_supplier');
    });
});
