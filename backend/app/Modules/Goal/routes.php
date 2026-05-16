<?php

use App\Modules\Goal\Controllers\GoalController;
use Illuminate\Support\Facades\Route;

Route::prefix('goals')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                   [GoalController::class, 'index']);
    Route::post('/',                  [GoalController::class, 'store']);
    Route::put('{goal}',              [GoalController::class, 'update']);
    Route::patch('{goal}/progress',   [GoalController::class, 'progress']);
    Route::delete('{goal}',           [GoalController::class, 'destroy']);
});
