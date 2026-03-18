<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\app\Http\Controllers\AuthenticationController;


Route::prefix("v1/users")->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [AuthenticationController::class, 'logout']);
    });

    Route::post('auth/login', [AuthenticationController::class, 'login']);
});
