<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\app\Http\Controllers\CityController;
use Modules\Location\app\Http\Controllers\StateController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/states')->group(function () {
        Route::get('/all', [StateController::class, 'index'])->middleware('permission:view_location_record');
    });
    Route::prefix('v1/cities')->group(function () {
        Route::get('/all-by-state', [CityController::class, 'index'])->middleware('permission:view_location_record');
    });
});