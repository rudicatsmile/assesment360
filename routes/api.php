<?php

use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

$adminGateMiddleware = (string) config('rbac.middleware_aliases.admin_gate', 'access.admin');

Route::middleware(['auth', $adminGateMiddleware])->group(function (): void {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
});
