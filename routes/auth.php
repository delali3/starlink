<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])
        ->name('admin.login');

    Route::post('admin/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('password/reset', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('password/email', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('password/reset/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('password/reset', [NewPasswordController::class, 'store'])
        ->name('password.update');
});
