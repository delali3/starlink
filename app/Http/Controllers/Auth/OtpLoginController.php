<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpLoginController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Show the OTP login form.
     */
    public function showLoginForm()
    {
        return view('auth.otp-login');
    }

    /**
     * Send OTP to user's phone.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
        ]);

        // Rate limit: 5 OTP requests per phone per hour
        $rateLimitKey = 'otp-send:' . $request->phone;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            throw ValidationException::withMessages([
                'phone' => ["Too many OTP requests. Please try again in " . ceil($seconds / 60) . " minute(s)."],
            ]);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        // Check if user exists
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ['No account found with this phone number.'],
            ]);
        }

        if ($user->isSuspended()) {
            throw ValidationException::withMessages([
                'phone' => ['Your account has been suspended. Please contact support.'],
            ]);
        }

        // Send OTP
        $result = $this->otpService->generateOtp($request->phone);

        return back()->withInput()->with('success', $result['message']);
    }

    /**
     * Verify OTP and login user.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        // Verify OTP
        $result = $this->otpService->verifyOtp($request->phone, $request->otp);

        if (!$result['status']) {
            throw ValidationException::withMessages([
                'otp' => [$result['message']],
            ]);
        }

        // Find user and login
        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->isSuspended()) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid credentials or account suspended.'],
            ]);
        }

        // Login user with remember me enabled (1 year)
        Auth::login($user, true);

        // Log the login
        AuditLog::log('user_login', $user);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Logout the user.
     */
    public function logout(Request $request)
    {
        AuditLog::log('user_logout');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
