<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\app\Http\Controllers\InventoryController;
use Modules\Inventory\app\Http\Controllers\GoodsReceiveNotesController;
use Modules\Inventory\app\Http\Controllers\OpeningStockController;
use Modules\Inventory\app\Http\Controllers\PurchaseOrderController;
use Modules\Inventory\app\Http\Controllers\PurchaseReturnController;
use Modules\Inventory\app\Http\Controllers\StockBalanceController;
use Modules\Inventory\app\Http\Controllers\StockMovementController;
use Modules\Inventory\app\Http\Controllers\StockTransferController;
use Modules\Inventory\app\Http\Controllers\UnitOfMeasurementController;
use Modules\Inventory\app\Http\Controllers\UnitOfMeasurementConversionController;

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

    Route::prefix('v1/opening-stocks')->group(function(){
        Route::get('', [OpeningStockController::class, 'openingStocks'])->middleware('permission:view_opening_stock');
        Route::post('', [OpeningStockController::class, 'create'])->middleware('permission:create_opening_stock');
        Route::get('{id}', [OpeningStockController::class, 'findOrFail'])->middleware('permission:view_opening_stock');
        Route::put('{id}', [OpeningStockController::class, 'update'])->middleware('permission:update_opening_stock');
        Route::delete('{id}', [OpeningStockController::class, 'delete'])->middleware('permission:delete_opening_stock');
        Route::patch('{id}/confirm', [OpeningStockController::class, 'confirm'])->middleware('permission:update_opening_stock');
     });

    Route::prefix('v1/stock-transfers')->group(function(){
        Route::get('', [StockTransferController::class, 'stockTransfers'])->middleware('permission:view_stock_transfer');
        Route::post('', [StockTransferController::class, 'create'])->middleware('permission:create_stock_transfer');
        Route::get('{id}', [StockTransferController::class, 'findOrFail'])->middleware('permission:view_stock_transfer');
        Route::put('{id}', [StockTransferController::class, 'update'])->middleware('permission:update_stock_transfer');
        Route::delete('{id}', [StockTransferController::class, 'delete'])->middleware('permission:delete_stock_transfer');
        Route::patch('{id}/confirm', [StockTransferController::class, 'confirm'])->middleware('permission:update_stock_transfer');
        Route::patch('{id}/reject', [StockTransferController::class, 'reject'])->middleware('permission:update_stock_transfer');
    });

    Route::prefix('v1/stock-ledgers')->group(function(){
        Route::get('', [StockMovementController::class, 'index'])->middleware('permission:view_stock_ledger');
    });

    Route::prefix('v1/stock-balances')->group(function(){
        Route::get('', [StockBalanceController::class, 'index'])->middleware('permission:view_stock_balance');
        Route::get('product/{productId}/lots', [StockBalanceController::class, 'lotTotals'])->middleware('permission:view_stock_balance');
    });

    Route::prefix('v1/purchase-orders')->group(function(){
        Route::post('calculate-line-total', [PurchaseOrderController::class, 'calculateLineTotal'])->middleware('permission:create_purchase_order');
        Route::post('calculate-total-amount', [PurchaseOrderController::class, 'calculateTotalAmount'])->middleware('permission:create_purchase_order');
        Route::get('', [PurchaseOrderController::class, 'index'])->middleware('permission:view_purchase_order');
        Route::post('', [PurchaseOrderController::class, 'store'])->middleware('permission:create_purchase_order');
        Route::get('{id}', [PurchaseOrderController::class, 'show'])->middleware('permission:view_purchase_order');
        Route::put('{id}', [PurchaseOrderController::class, 'update'])->middleware('permission:update_purchase_order');
        Route::delete('{id}', [PurchaseOrderController::class, 'destroy'])->middleware('permission:delete_purchase_order');
        Route::put('{id}/status', [PurchaseOrderController::class, 'updateStatus'])->middleware('permission:update_purchase_order');
        Route::put('{id}/payment-status', [PurchaseOrderController::class, 'updatePaymentStatus'])->middleware('permission:update_purchase_order');
        Route::put('{id}/delivery-status', [PurchaseOrderController::class, 'updateDeliveryStatus'])->middleware('permission:update_purchase_order');
    });

    Route::prefix('v1/goods-receive-notes')->group(function () {
        Route::get('', [GoodsReceiveNotesController::class, 'index'])->middleware('permission:view_goods_receive_note');
        Route::post('', [GoodsReceiveNotesController::class, 'store'])->middleware('permission:create_goods_receive_note');
        Route::get('{id}', [GoodsReceiveNotesController::class, 'show'])->middleware('permission:view_goods_receive_note');
        Route::get('{id}/returnable-lines', [PurchaseReturnController::class, 'returnableLines'])->middleware('permission:view_goods_receive_note');
        Route::put('{id}', [GoodsReceiveNotesController::class, 'update'])->middleware('permission:update_goods_receive_note');
        Route::delete('{id}', [GoodsReceiveNotesController::class, 'delete'])->middleware('permission:delete_goods_receive_note');
        Route::patch('{id}/approve', [GoodsReceiveNotesController::class, 'approve'])->middleware('permission:approve_goods_receive_note');
        Route::patch('{id}/reject', [GoodsReceiveNotesController::class, 'reject'])->middleware('permission:reject_goods_receive_note');
    });

    Route::prefix('v1/purchase-returns')->group(function () {
        Route::get('', [PurchaseReturnController::class, 'index'])->middleware('permission:view_purchase_return');
        Route::post('', [PurchaseReturnController::class, 'store'])->middleware('permission:create_purchase_return');
        Route::get('{id}', [PurchaseReturnController::class, 'show'])->middleware('permission:view_purchase_return');
        Route::put('{id}', [PurchaseReturnController::class, 'update'])->middleware('permission:update_purchase_return');
        Route::patch('{id}/approve', [PurchaseReturnController::class, 'approve'])->middleware('permission:approve_purchase_return');
        Route::patch('{id}/reject', [PurchaseReturnController::class, 'reject'])->middleware('permission:reject_purchase_return');
    });
});
