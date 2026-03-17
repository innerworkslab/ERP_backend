<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessControl\Http\Controllers\AccessControlController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accesscontrols', AccessControlController::class)->names('accesscontrol');
});
