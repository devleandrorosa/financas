<?php

use App\Modules\Projection\Controllers\ProjectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('projection')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/', [ProjectionController::class, 'index']);
});
