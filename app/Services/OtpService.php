<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate and send OTP to phone number.
     *
     * @param string $phone
     * @return array
     */
    public function generateOtp(string $phone): array
    {
        // Delete any existing OTPs for this phone
        OtpVerification::where('phone', $phone)->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Create OTP record
        $otpVerification = OtpVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send OTP (implement your SMS service here)
        $this->sendOtp($phone, $otp);

        return [
            'status' => true,
            'message' => 'OTP sent successfully',
            'expires_in' => '10 minutes',
        ];
    }

    /**
     * Verify OTP for phone number.
     *
     * @param string $phone
     * @param string $otp
     * @return array
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

        // Mark as verified
        $otpVerification->markAsVerified();

        return [
            'status' => true,
            'message' => 'OTP verified successfully',
        ];
    }

    /**
     * Send OTP via SMS using Frog SMS API.
     *
     * @param string $phone
     * @param string $otp
     * @return void
     */
    private function sendOtp(string $phone, string $otp): void
    {
        $message = "Your GhLinks verification code is: {$otp}. Valid for 10 minutes.";

        // In development, just log the OTP
        if (config('app.env') !== 'production') {
            Log::info("OTP for {$phone}: {$otp}");
            return;
        }

        // Format phone number for Ghana
        $formattedPhone = $this->formatPhoneNumber($phone);

        try {
            // Send SMS via Frog API v3
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'verify' => false, // Disable SSL verification for local development
            ])->withHeaders([
                'Content-Type' => 'application/json',
                'API-KEY' => config('services.frog_sms.api_key'),
                'USERNAME' => config('services.frog_sms.username'),
            ])->post('https://frogapi.wigal.com.gh/api/v3/sms/send', [
                'senderid' => config('services.frog_sms.sender_id'),
                'destinations' => [[
                    'destination' => $formattedPhone,
                    'msgid' => 'OTP-' . time() . '-' . substr($formattedPhone, -4)
                ]],
                'message' => $message,
                'smstype' => 'text'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'ACCEPTD') {
                    Log::info("OTP sent successfully to {$phone}", ['response' => $responseData]);
                } else {
                    Log::warning("OTP send may have failed for {$phone}", ['response' => $responseData]);
                }
            } else {
                Log::error("Failed to send OTP to {$phone}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception sending OTP to {$phone}: {$e->getMessage()}");
        }
    }

    /**
     * Format phone number for Ghana (0XXXXXXXXX format for Frog API).
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces, dashes, or parentheses
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Frog API accepts Ghana format with leading 0
        // If it starts with 233, convert to 0
        if (substr($phone, 0, 3) === '233') {
            return '0' . substr($phone, 3);
        }

        // If already starts with 0, return as is
        if (substr($phone, 0, 1) === '0') {
            return $phone;
        }

        // Otherwise, add 0 prefix
        return '0' . $phone;
    }
}
