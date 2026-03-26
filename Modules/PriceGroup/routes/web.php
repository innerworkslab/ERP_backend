<?php

use Illuminate\Support\Facades\Route;
use Modules\PriceGroup\app\Http\Controllers\SellingPriceGroupController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pricegroups', SellingPriceGroupController::class)->names('pricegroup');
});
