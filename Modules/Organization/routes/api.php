<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\app\Http\Controllers\BranchController;
use Modules\Organization\app\Http\Controllers\CurrencyController;
use Modules\Organization\app\Http\Controllers\DepartmentController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/branches')->group(function () {
        Route::patch('{id}/toggle-status', [BranchController::class, 'toggleActive'])->middleware('permission:update_branch');
        Route::get('/all', [BranchController::class, 'index'])->middleware('permission:view_branch');
        Route::post('', [BranchController::class, 'create'])->middleware('permission:create_branch');
        Route::put('{id}', [BranchController::class, 'update'])->middleware('permission:update_branch');
        Route::get('{id}', [BranchController::class, 'findOrFail'])->middleware('permission:view_branch');
    });

    Route::prefix('v1/departments')->group(function () {
        Route::patch('{id}/toggle-status', [DepartmentController::class, 'toggleActive'])->middleware('permission:update_department');
        Route::get('/all', [DepartmentController::class, 'index'])->middleware('permission:view_department');
        Route::get('/by-branch', [DepartmentController::class, 'departmentsBySelectedBranch'])->middleware('permission:view_department');
        Route::post('', [DepartmentController::class, 'create'])->middleware('permission:create_department');
        Route::put('{id}', [DepartmentController::class, 'update'])->middleware('permission:update_department');
        Route::get('{id}', [DepartmentController::class, 'findOrFail'])->middleware('permission:view_department');
    });

    Route::prefix('v1/currencies')->group(function () {
        Route::get('', [CurrencyController::class, 'index'])->middleware('permission:view_currency');
        Route::post('', [CurrencyController::class, 'create'])->middleware('permission:create_currency');
        Route::put('{id}', [CurrencyController::class, 'update'])->middleware('permission:update_currency');
        Route::get('{id}', [CurrencyController::class, 'findOrFail'])->middleware('permission:view_currency');
        Route::delete('{id}', [CurrencyController::class, 'delete'])->middleware('permission:delete_currency');
        Route::get('{id}/exchange-rate-history', [CurrencyController::class, 'exchangeRateHistory'])->middleware('permission:view_currency');
        Route::put('{id}/update-exchange-rate', [CurrencyController::class, 'updateExchangeRate'])->middleware('permission:update_currency');
        Route::put('{id}/set-base-currency', [CurrencyController::class, 'setBaseCurrency'])->middleware('permission:update_currency');
    });
});
