<?php

use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// OTP Authentication Routes (for users)
Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login/send-otp', [OtpLoginController::class, 'sendOtp'])->name('login.send-otp');
    Route::post('/login/verify-otp', [OtpLoginController::class, 'verifyOtp'])->name('login.verify-otp');
});

// Admin/SuperAdmin Password Login
require __DIR__.'/auth.php';

// Authenticated Routes
Route::middleware(['auth', 'check.user.status'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Logout
    Route::post('/logout', [OtpLoginController::class, 'logout'])->name('logout');
    
    // User Routes (accessible by users)
    Route::middleware('role:user')->group(function () {
        Route::post('/payment/initialize', [PaymentController::class, 'initialize'])->name('payment.initialize');
        Route::get('/payment/pending/{reference}', [PaymentController::class, 'pending'])->name('payment.pending');
        Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
    });
    
    // Admin Routes (accessible by admin and superadmin)
    Route::middleware('role:admin|superadmin')->group(function () {
        // User Management
        Route::resource('users', UserManagementController::class)->except(['edit', 'update', 'destroy']);
        Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
        Route::get('/users/unpaid/list', [UserManagementController::class, 'unpaid'])->name('users.unpaid');
        
        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    });
    
    // SuperAdmin Only Routes
    Route::middleware('role:superadmin')->group(function () {
        // Admin Management
        Route::resource('admins', AdminManagementController::class)->except(['edit', 'update', 'show']);
    });
});

// Payment Callback Route (no auth required)
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payments/verify', [PaymentController::class, 'verify'])->name('payments.verify');
Route::get('/payments/cancelled', [PaymentController::class, 'cancelled'])->name('payments.cancelled');

// Payment Status Check (AJAX)
Route::get('/api/payment/{reference}/status', [PaymentController::class, 'checkStatus'])->name('payment.check-status');
