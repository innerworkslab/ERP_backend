<?php

use Illuminate\Support\Facades\Route;
use Modules\Sale\app\Http\Controllers\DeliveryProviderController;
use Modules\Sale\app\Http\Controllers\SaleInvoiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Delivery Provider
    Route::prefix('delivery-providers')->group(function () {
        Route::patch('{id}/toggle-status', [DeliveryProviderController::class, 'toggleActive'])->middleware('permission:update_delivery_provider');
        Route::get('', [DeliveryProviderController::class, 'index'])->middleware('permission:view_delivery_provider');
        Route::post('', [DeliveryProviderController::class, 'create'])->middleware('permission:create_delivery_provider');
        Route::put('{id}', [DeliveryProviderController::class, 'update'])->middleware('permission:update_delivery_provider');
        Route::get('{id}', [DeliveryProviderController::class, 'findOrFail'])->middleware('permission:view_delivery_provider');
    });

    // Sale Invoice
    Route::prefix('sale-invoices')->group(function () {
        Route::patch('{id}/pending', [SaleInvoiceController::class, 'pending'])->middleware('permission:pending_sale_invoice');
        Route::patch('{id}/ordered', [SaleInvoiceController::class, 'ordered'])->middleware('permission:ordered_sale_invoice');
        Route::patch('{id}/reserved', [SaleInvoiceController::class, 'reserved'])->middleware('permission:reserved_sale_invoice');
        Route::patch('{id}/delivered', [SaleInvoiceController::class, 'delivered'])->middleware('permission:delivered_sale_invoice');
        Route::get('', [SaleInvoiceController::class, 'index'])->middleware('permission:view_sale_invoice');
        Route::post('', [SaleInvoiceController::class, 'create'])->middleware('permission:create_sale_invoice');
        Route::put('{id}', [SaleInvoiceController::class, 'update'])->middleware('permission:update_sale_invoice');
        Route::delete('{id}', [SaleInvoiceController::class, 'delete'])->middleware('permission:delete_sale_invoice');
        Route::get('{id}', [SaleInvoiceController::class, 'findOrFail'])->middleware('permission:view_sale_invoice');
    });
});
