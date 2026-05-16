<?php

use App\Modules\BankAccount\Controllers\BankAccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('bank-accounts')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                  [BankAccountController::class, 'index']);
    Route::post('/',                 [BankAccountController::class, 'store']);
    Route::put('{bankAccount}',      [BankAccountController::class, 'update']);
    Route::delete('{bankAccount}',   [BankAccountController::class, 'destroy']);
});
