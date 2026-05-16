<?php

use App\Modules\Transaction\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                  [TransactionController::class, 'index']);
    Route::post('/',                 [TransactionController::class, 'store']);
    Route::put('{transaction}',      [TransactionController::class, 'update']);
    Route::delete('{transaction}',   [TransactionController::class, 'destroy']);
});
