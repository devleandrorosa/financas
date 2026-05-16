<?php

use App\Modules\AI\Controllers\AIImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('import',                      [AIImportController::class, 'upload']);
    Route::get('import/{session}',             [AIImportController::class, 'status']);
    Route::post('import/{session}/confirm',    [AIImportController::class, 'confirm']);
});
