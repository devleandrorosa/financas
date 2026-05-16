<?php

use App\Modules\Investment\Controllers\InvestmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('investments')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                [InvestmentController::class, 'index']);
    Route::post('/',               [InvestmentController::class, 'store']);
    Route::put('{investment}',     [InvestmentController::class, 'update']);
    Route::delete('{investment}',  [InvestmentController::class, 'destroy']);
});
