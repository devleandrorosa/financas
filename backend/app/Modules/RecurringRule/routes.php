<?php

use App\Modules\RecurringRule\Controllers\RecurringRuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('recurring-rules')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                         [RecurringRuleController::class, 'index']);
    Route::post('/',                        [RecurringRuleController::class, 'store']);
    Route::put('{recurringRule}',           [RecurringRuleController::class, 'update']);
    Route::patch('{recurringRule}/toggle',  [RecurringRuleController::class, 'toggle']);
    Route::delete('{recurringRule}',        [RecurringRuleController::class, 'destroy']);
});
