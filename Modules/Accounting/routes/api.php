<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\app\Http\Controllers\AccountController;
use Modules\Accounting\app\Http\Controllers\CashbookController;
use Modules\Accounting\app\Http\Controllers\CashbookLedgerController;
use Modules\Accounting\app\Http\Controllers\CashbookTransactionController;
use Modules\Accounting\app\Http\Controllers\CashbookTransferController;
use Modules\Accounting\app\Http\Controllers\CashbookAdjustmentController;

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

    Route::prefix('v1/cashbook-ledgers')->group(function () {
        Route::get('', [CashbookLedgerController::class, 'index'])->middleware('permission:view_cashbook_ledger');
    });

    Route::prefix('v1/cashbook-transfers')->group(function () {
        Route::patch('{id}/confirm', [CashbookTransferController::class, 'confirmTransfer'])->middleware('permission:confirm_cashbook_transfer');
        Route::patch('{id}/reject', [CashbookTransferController::class, 'rejectTransfer'])->middleware('permission:update_cashbook_transfer');
        Route::get('', [CashbookTransferController::class, 'index'])->middleware('permission:view_cashbook_transfer');
        Route::post('', [CashbookTransferController::class, 'create'])->middleware('permission:create_cashbook_transfer');
        Route::put('{id}', [CashbookTransferController::class, 'update'])->middleware('permission:update_cashbook_transfer');
        Route::get('{id}', [CashbookTransferController::class, 'findOrFail'])->middleware('permission:view_cashbook_transfer');
    });

    //adjustment routes
    Route::prefix('v1/cashbook-adjustments')->group(function () {
        Route::patch('{id}/approve', [CashbookAdjustmentController::class, 'approve'])->middleware('permission:approve_cashbook_adjustment');
        Route::patch('{id}/reject', [CashbookAdjustmentController::class, 'reject'])->middleware('permission:update_cashbook_adjustment');
        Route::get('', [CashbookAdjustmentController::class, 'index'])->middleware('permission:view_cashbook_adjustment');
        Route::post('', [CashbookAdjustmentController::class, 'create'])->middleware('permission:create_cashbook_adjustment');
        Route::put('{id}', [CashbookAdjustmentController::class, 'update'])->middleware('permission:update_cashbook_adjustment');
        Route::get('{id}', [CashbookAdjustmentController::class, 'findOrFail'])->middleware('permission:view_cashbook_adjustment');
    });
});
