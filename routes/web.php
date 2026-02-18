<?php

use App\Http\Controllers\AdminManagementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\BulkSmsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Welcome page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// OTP Authentication Routes (for users)
Route::middleware('guest')->group(function () {
    Route::get('/login', [OtpLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login/send-otp', [OtpLoginController::class, 'sendOtp'])->name('login.send-otp');
    Route::post('/login/verify-otp', [OtpLoginController::class, 'verifyOtp'])->name('login.verify-otp');
});

// Admin/SuperAdmin Password Login
require __DIR__.'/auth.php';

// Authenticated Routes
Route::middleware(['auth', 'check.user.status', 'check.org.status'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Logout
    Route::post('/logout', [OtpLoginController::class, 'logout'])->name('logout');

    // Profile (all authenticated users)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // User Routes (accessible by users)
    Route::middleware('role:user')->group(function () {
        Route::post('/payment/initialize', [PaymentController::class, 'initialize'])->name('payment.initialize');
        Route::get('/payment/pending/{reference}', [PaymentController::class, 'pending'])->name('payment.pending');
        Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
    });

    // Admin Routes (accessible by admin and superadmin)
    Route::middleware('role:admin|superadmin')->group(function () {
        // User Management
        Route::resource('users', UserManagementController::class)->except(['destroy']);
        Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
        Route::get('/users/unpaid/list', [UserManagementController::class, 'unpaid'])->name('users.unpaid');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy')->middleware('role:superadmin');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/cash/{user}', [PaymentController::class, 'manualCreate'])->name('payments.manual.create');
        Route::post('/payments/cash/{user}', [PaymentController::class, 'manualStore'])->name('payments.manual.store');

        // Bulk SMS
        Route::get('/sms', [BulkSmsController::class, 'index'])->name('sms.index');
        Route::get('/sms/compose', [BulkSmsController::class, 'create'])->name('sms.create');
        Route::post('/sms/send', [BulkSmsController::class, 'store'])->name('sms.store');

        // Exports
        Route::get('/export/payments', [ExportController::class, 'payments'])->name('export.payments');
        Route::get('/export/users', [ExportController::class, 'users'])->name('export.users');
    });

    // SuperAdmin Only Routes
    Route::middleware('role:superadmin')->group(function () {
        // Admin Management
        Route::resource('admins', AdminManagementController::class)->except(['edit', 'update', 'show']);

        // Organization Management
        Route::resource('organizations', OrganizationController::class);
        Route::post('/organizations/{organization}/suspend', [OrganizationController::class, 'suspend'])->name('organizations.suspend');
        Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate'])->name('organizations.activate');
        Route::post('/organizations/{organization}/assign-admin', [OrganizationController::class, 'assignAdmin'])->name('organizations.assign-admin');
        Route::post('/admin/switch-org', [OrganizationController::class, 'switchOrg'])->name('admin.switch-org');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

// Payment Callback Route (no auth required)
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payments/verify', [PaymentController::class, 'verify'])->name('payments.verify');
Route::get('/payments/cancelled', [PaymentController::class, 'cancelled'])->name('payments.cancelled');

// Payment Status Check (AJAX)
Route::get('/api/payment/{reference}/status', [PaymentController::class, 'checkStatus'])->name('payment.check-status');
