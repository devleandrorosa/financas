<?php

use App\Modules\Category\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',     [CategoryController::class, 'index']);
    Route::get('flat',  [CategoryController::class, 'flat']);
    Route::post('/',    [CategoryController::class, 'store']);
    Route::put('{category}',    [CategoryController::class, 'update']);
    Route::delete('{category}', [CategoryController::class, 'destroy']);
});
