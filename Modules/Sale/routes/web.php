<?php

use Illuminate\Support\Facades\Route;
use Modules\Sale\Http\Controllers\SaleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sales', SaleController::class)->names('sale');
});
