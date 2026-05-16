<?php

use App\Modules\Budget\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;

Route::prefix('budgets')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',           [BudgetController::class, 'index']);
    Route::post('/',          [BudgetController::class, 'store']);
    Route::delete('{budget}', [BudgetController::class, 'destroy']);
});
