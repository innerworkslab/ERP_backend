<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\app\Http\Controllers\FeatureRecommendationRuleController;
use Modules\Staff\app\Http\Controllers\Nrc\NrcController;
use Modules\Staff\app\Http\Controllers\StaffController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('nrc/{nrc_code}/township-code', [NrcController::class, 'getTownshipCodesByNrcCode']);

    Route::prefix('staffs')->group(function () {
        Route::get('/feature-suggestions', [StaffController::class, 'featureSuggestions'])->middleware('permission:view_user');
        Route::get('/', [StaffController::class, 'index'])->middleware('permission:view_user');
        Route::post('/', [StaffController::class, 'store'])->middleware('permission:create_user');
        Route::get('/{id}', [StaffController::class, 'show'])->middleware('permission:view_user');
        Route::post('/{id}', [StaffController::class, 'update'])->middleware('permission:update_user');
        Route::delete('/{id}', [StaffController::class, 'destroy'])->middleware('permission:delete_user');
        Route::patch('/{id}/toggle-status', [StaffController::class, 'toggleActive'])->middleware('permission:update_user');
        Route::patch('/{id}/change-password', [StaffController::class, 'changePassword'])->middleware('permission:update_user');
    });

    Route::prefix('feature-recommendation-rules')->group(function () {
        Route::get('/', [FeatureRecommendationRuleController::class, 'index'])->middleware('permission:view_feature_recommendation_rule');
        Route::post('/', [FeatureRecommendationRuleController::class, 'store'])->middleware('permission:create_feature_recommendation_rule');
        Route::get('/{id}', [FeatureRecommendationRuleController::class, 'show'])->middleware('permission:view_feature_recommendation_rule');
        Route::post('/{id}', [FeatureRecommendationRuleController::class, 'update'])->middleware('permission:update_feature_recommendation_rule');
        Route::delete('/{id}', [FeatureRecommendationRuleController::class, 'destroy'])->middleware('permission:delete_feature_recommendation_rule');
    });

    
});
