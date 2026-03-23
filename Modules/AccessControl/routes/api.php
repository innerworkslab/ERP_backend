<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessControl\app\Http\Controllers\FeatureController;
use Modules\AccessControl\app\Http\Controllers\RoleController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/roles')->group(function () {
        Route::patch('{id}/toggle-status', [RoleController::class, 'toggleActive'])->middleware('permission:update_role');
        Route::get('/all', [RoleController::class, 'index'])->middleware('permission:view_role');
        Route::get('/no-pagination', [RoleController::class, 'rolesWithoutPagination'])->middleware('permission:view_role');
        Route::post('', [RoleController::class, 'create'])->middleware('permission:create_role');
        Route::put('{id}', [RoleController::class, 'update'])->middleware('permission:update_role');
        Route::get('{id}', [RoleController::class, 'findOrFail'])->middleware('permission:view_role');
    });

    Route::prefix('v1/features')->group(function () {
        Route::patch('{id}/toggle-status', [FeatureController::class, 'toggleActive'])->middleware('permission:update_feature');
        Route::get('/all', [FeatureController::class, 'index'])->middleware('permission:view_feature');
        Route::post('/assign-roles', [FeatureController::class, 'assignRoles'])->middleware('permission:assign_role_to_feature');
        Route::get('{id}', [FeatureController::class, 'findOrFail'])->middleware('permission:view_feature');
        Route::get('{roleId}/recommended-features', [FeatureController::class, 'recommendedFeatures'])->middleware('permission:view_feature');
        Route::get('{roleId}/other-features', [FeatureController::class, 'otherFeatures'])->middleware('permission:view_feature');

    });
});
