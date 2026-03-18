<?php

use Illuminate\Support\Facades\Route;
use Modules\Stakeholder\Http\Controllers\StakeholderController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('stakeholders', StakeholderController::class)->names('stakeholder');
});
