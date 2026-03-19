<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\app\Http\Controllers\BranchController;
use Modules\Organization\app\Http\Controllers\DepartmentController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('v1/branches')->group(function () {
        Route::patch('{id}/toggle-status', [BranchController::class, 'toggleActive'])->middleware('permission:update_branch');
        Route::get('/all', [BranchController::class, 'index'])->middleware('permission:view_branch');
        Route::post('', [BranchController::class, 'create'])->middleware('permission:create_branch');
        Route::put('{id}', [BranchController::class, 'update'])->middleware('permission:update_branch');
        Route::get('{id}', [BranchController::class, 'findOrFail'])->middleware('permission:view_branch');
    });

    Route::prefix('v1/departments')->group(function () {
        Route::patch('{id}/toggle-status', [DepartmentController::class, 'toggleActive'])->middleware('permission:update_department');
        Route::get('/all', [DepartmentController::class, 'index'])->middleware('permission:view_department');
        Route::get('/by-branch', [DepartmentController::class, 'departmentsBySelectedBranch'])->middleware('permission:view_department');
        Route::post('', [DepartmentController::class, 'create'])->middleware('permission:create_department');
        Route::put('{id}', [DepartmentController::class, 'update'])->middleware('permission:update_department');
        Route::get('{id}', [DepartmentController::class, 'findOrFail'])->middleware('permission:view_department');
    });
});
