<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\app\Http\Controllers\BrandController;
use Modules\Product\app\Http\Controllers\CategoryController;
use Modules\Product\app\Http\Controllers\CollectionController;
use Modules\Product\app\Http\Controllers\OriginCountryController;
use Modules\Product\app\Http\Controllers\ProductController;
use Modules\Product\app\Http\Controllers\TaxController;
use Modules\Product\app\Http\Controllers\VariationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/variations')->group(function () {
        Route::patch('{id}/toggle-status', [VariationController::class, 'toggleActive'])->middleware('permission:update_variation');
        Route::get('/all', [VariationController::class, 'index'])->middleware('permission:view_variation');
        Route::post('', [VariationController::class, 'create'])->middleware('permission:create_variation');
        Route::put('{id}', [VariationController::class, 'update'])->middleware('permission:update_variation');
        Route::get('{id}', [VariationController::class, 'findOrFail'])->middleware('permission:view_variation');
    });

    Route::prefix('v1/brands')->group(function () {
        Route::patch('{id}/toggle-status', [BrandController::class, 'toggleActive'])->middleware('permission:update_brand');
        Route::get('', [BrandController::class, 'index'])->middleware('permission:view_brand');
        Route::post('', [BrandController::class, 'create'])->middleware('permission:create_brand');
        Route::put('{id}', [BrandController::class, 'update'])->middleware('permission:update_brand');
        Route::delete('{id}', [BrandController::class, 'delete'])->middleware('permission:delete_brand');
        Route::get('{id}', [BrandController::class, 'findOrFail'])->middleware('permission:view_brand');
    });

    Route::prefix('v1/categories')->group(function () {
        Route::patch('{id}/toggle-status', [CategoryController::class, 'toggleActive'])->middleware('permission:update_category');
        Route::get('', [CategoryController::class, 'index'])->middleware('permission:view_category');
        Route::post('', [CategoryController::class, 'create'])->middleware('permission:create_category');
        Route::put('{id}', [CategoryController::class, 'update'])->middleware('permission:update_category');
        Route::delete('{id}', [CategoryController::class, 'delete'])->middleware('permission:delete_category');
        Route::get('{id}', [CategoryController::class, 'findOrFail'])->middleware('permission:view_category');
    });

    Route::prefix('v1/taxes')->group(function () {
        Route::patch('{id}/toggle-status', [TaxController::class, 'toggleActive'])->middleware('permission:update_tax');
        Route::get('', [TaxController::class, 'index'])->middleware('permission:view_tax');
        Route::post('', [TaxController::class, 'create'])->middleware('permission:create_tax');
        Route::put('{id}', [TaxController::class, 'update'])->middleware('permission:update_tax');
        Route::delete('{id}', [TaxController::class, 'delete'])->middleware('permission:delete_tax');
        Route::get('{id}', [TaxController::class, 'findOrFail'])->middleware('permission:view_tax');
    });

    Route::prefix('v1/origin-countries')->group(function() {
        Route::get('', [OriginCountryController::class, 'index'])->middleware('permission:view_product'); 
        Route::post('', [OriginCountryController::class, 'create'])->middleware('permission:create_product');
        Route::put('{id}', [OriginCountryController::class, 'update'])->middleware('permission:update_product');
        Route::delete('{id}', [OriginCountryController::class, 'delete'])->middleware('permission:delete_product');
        Route::get('{id}', [OriginCountryController::class, 'findOrFail'])->middleware('permission:view_product');
    });

    Route::prefix('v1/products')->group(function () {
        Route::patch('{id}/toggle-status', [ProductController::class, 'toggleActive'])->middleware('permission:update_product');
        Route::get('', [ProductController::class, 'index'])->middleware('permission:view_product');
        Route::post('', [ProductController::class, 'create'])->middleware('permission:create_product');
        Route::post('{id}', [ProductController::class, 'update'])->middleware('permission:update_product');
        Route::delete('{id}', [ProductController::class, 'delete'])->middleware('permission:delete_product');
        Route::get('{id}', [ProductController::class, 'findOrFail'])->middleware('permission:view_product');
    });

    Route::prefix('v1/collections')->group(function () {
        Route::patch('{id}/toggle-status', [CollectionController::class, 'toggleActive'])->middleware('permission:update_collection');
        Route::get('', [CollectionController::class, 'index'])->middleware('permission:view_collection');
        Route::post('', [CollectionController::class, 'create'])->middleware('permission:create_collection');
        Route::post('{id}', [CollectionController::class, 'update'])->middleware('permission:update_collection');
        Route::delete('{id}', [CollectionController::class, 'delete'])->middleware('permission:delete_collection');
        Route::get('{id}', [CollectionController::class, 'findOrFail'])->middleware('permission:view_collection');
        Route::get('{id}/products', [CollectionController::class, 'getProducts'])->middleware('permission:view_collection');
        Route::post('{id}/products', [CollectionController::class, 'addProducts'])->middleware('permission:update_collection');
    });

}); 
