<?php

use App\Modules\Family\Controllers\FamilyController;
use Illuminate\Support\Facades\Route;

Route::prefix('family')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/',                         [FamilyController::class, 'show']);
    Route::post('invite',                   [FamilyController::class, 'invite']);
    Route::delete('members/{id}',           [FamilyController::class, 'removeMember']);
    Route::patch('members/{id}/role',       [FamilyController::class, 'updateRole']);
});
