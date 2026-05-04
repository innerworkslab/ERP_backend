<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholder\app\Http\Controllers\Customer\CustomerController;
use Modules\Stakeholder\app\Http\Controllers\CustomerType\CustomerTypeController;
use Modules\Stakeholder\app\Http\Controllers\Supplier\SupplierController;
use Modules\Stakeholder\app\Http\Controllers\SupplierType\SupplierTypeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    //Customer Routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->middleware('permission:view_customer');
        Route::post('/', [CustomerController::class, 'store'])->middleware('permission:create_customer');
        Route::get('/{id}', [CustomerController::class, 'show'])->middleware('permission:view_customer');
        Route::put('/{id}', [CustomerController::class, 'update'])->middleware('permission:update_customer');
        Route::patch('/{id}/toggle-status', [CustomerController::class, 'toggleStatus'])->middleware('permission:update_customer');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('permission:delete_customer');
    });

    //Customer Type Routes
    Route::prefix('customer-types')->group(function () {
        Route::get('/', [CustomerTypeController::class, 'index'])->middleware('permission:view_customer');
        Route::post('/', [CustomerTypeController::class, 'store'])->middleware('permission:create_customer');
    });

    //Supplier Routes
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('permission:view_supplier');
        Route::post('/', [SupplierController::class, 'store'])->middleware('permission:create_supplier');
        Route::get('/{id}', [SupplierController::class, 'show'])->middleware('permission:view_supplier');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('permission:update_supplier');
        Route::patch('/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->middleware('permission:update_supplier');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('permission:delete_supplier');
    });

    //Supplier Type Routes
    Route::prefix('supplier-types')->group(function () {
        Route::get('/', [SupplierTypeController::class, 'index'])->middleware('permission:view_supplier');
        Route::post('/', [SupplierTypeController::class, 'store'])->middleware('permission:create_supplier');
    });
});
