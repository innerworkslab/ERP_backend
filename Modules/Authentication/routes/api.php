<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\App\Http\Controllers\AuthenticationController;


Route::prefix("v1/users")->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [AuthenticationController::class, 'logout']);
    });

    Route::post('auth/login', [AuthenticationController::class, 'login']);
});
