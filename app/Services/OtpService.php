<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(
        private SmsService $smsService
    ) {}

    /**
     * Generate and send OTP to phone number.
     */
    public function generateOtp(string $phone): array
    {
        OtpVerification::where('phone', $phone)->delete();

        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        $message = "Your GhLinks verification code is: {$otp}. Valid for 10 minutes.";

        if (config('app.env') !== 'production') {
            Log::info("OTP for {$phone}: {$otp}");
        } else {
            $this->smsService->send($phone, $message);
        }

        return [
            'status' => true,
            'message' => 'OTP sent successfully',
            'expires_in' => '10 minutes',
        ];
    }

    /**
     * Verify OTP for phone number.
     */
    public function verifyOtp(string $phone, string $otp): array
    {
        $otpVerification = OtpVerification::where('phone', $phone)
            ->where('otp', $otp)
            ->where('verified', false)
            ->first();

        if (!$otpVerification) {
            return [
                'status' => false,
                'message' => 'Invalid OTP',
            ];
        }

        if ($otpVerification->isExpired()) {
            return [
                'status' => false,
                'message' => 'OTP has expired',
            ];
        }

        $otpVerification->markAsVerified();

        return [
            'status' => true,
            'message' => 'OTP verified successfully',
        ];
    }
}
