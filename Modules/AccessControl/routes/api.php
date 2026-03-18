<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessControl\app\Http\Controllers\RoleController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/roles')->group(function () {
        Route::patch('{id}/toggle-status', [RoleController::class, 'toggleActive'])->middleware('feature:update_role');
        Route::get('/all', [RoleController::class, 'index'])->middleware('feature:view_role');
        Route::post('', [RoleController::class, 'create'])->middleware('feature:create_role');
        Route::put('{id}', [RoleController::class, 'update'])->middleware('feature:update_role');
        Route::get('{id}', [RoleController::class, 'findOrFail'])->middleware('feature:view_role');
    });
});
