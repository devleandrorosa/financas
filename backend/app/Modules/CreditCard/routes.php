<?php

use App\Modules\CreditCard\Controllers\CreditCardController;
use Illuminate\Support\Facades\Route;

Route::prefix('credit-cards')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                          [CreditCardController::class, 'index']);
    Route::post('/',                         [CreditCardController::class, 'store']);
    Route::put('{creditCard}',               [CreditCardController::class, 'update']);
    Route::delete('{creditCard}',            [CreditCardController::class, 'destroy']);
    Route::get('{creditCard}/statements',    [CreditCardController::class, 'statements']);
    Route::patch('statements/{statement}/pay', [CreditCardController::class, 'payStatement']);
});
