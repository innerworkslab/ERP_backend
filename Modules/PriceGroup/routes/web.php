<?php

use Illuminate\Support\Facades\Route;
use Modules\PriceGroup\app\Http\Controllers\PriceGroupController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pricegroups', PriceGroupController::class)->names('pricegroup');
});
