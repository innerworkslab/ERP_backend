<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\app\Http\Controllers\AccountController;
use Modules\Accounting\app\Http\Controllers\CashbookController;
use Modules\Accounting\app\Http\Controllers\CashbookTransactionController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/accounts')->group(function () {
        Route::get('/all', [AccountController::class, 'index'])->middleware('permission:view_account');
    });

    Route::prefix('v1/cashbooks')->group(function () {
        Route::patch('{id}/toggle-status', [CashbookController::class, 'toggleActive'])->middleware('permission:update_cashbook');
        Route::get('/all', [CashbookController::class, 'index'])->middleware('permission:view_cashbook');
        Route::post('', [CashbookController::class, 'create'])->middleware('permission:create_cashbook');
        Route::put('{id}', [CashbookController::class, 'update'])->middleware('permission:update_cashbook');
        Route::get('{id}', [CashbookController::class, 'findOrFail'])->middleware('permission:view_cashbook');
    });

    Route::prefix('v1/cashbook-transactions')->group(function () {
        Route::patch('{id}/confirm', [CashbookTransactionController::class, 'confirm'])->middleware('permission:confirm_cashbook_transaction');
        Route::get('/all', [CashbookTransactionController::class, 'index'])->middleware('permission:view_cashbook_transaction');
        Route::post('', [CashbookTransactionController::class, 'create'])->middleware('permission:create_cashbook_transaction');
        Route::post('{id}', [CashbookTransactionController::class, 'update'])->middleware('permission:update_cashbook_transaction');
        Route::get('{id}', [CashbookTransactionController::class, 'findOrFail'])->middleware('permission:view_cashbook_transaction');
    });
});