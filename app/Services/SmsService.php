<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS message.
     */
    public function send(string $phone, string $message): bool
    {
        if (config('app.env') !== 'production') {
            Log::info("SMS to {$phone}: {$message}");
            return true;
        }

        $formattedPhone = $this->formatPhoneNumber($phone);

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'Content-Type' => 'application/json',
                'API-KEY' => config('services.frog_sms.api_key'),
                'USERNAME' => config('services.frog_sms.username'),
            ])->post('https://frogapi.wigal.com.gh/api/v3/sms/send', [
                'senderid' => config('services.frog_sms.sender_id'),
                'destinations' => [[
                    'destination' => $formattedPhone,
                    'msgid' => 'SMS-' . time() . '-' . substr($formattedPhone, -4)
                ]],
                'message' => $message,
                'smstype' => 'text'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === 'ACCEPTD') {
                    Log::info("SMS sent successfully to {$phone}");
                    return true;
                }
                Log::warning("SMS may have failed for {$phone}", ['response' => $responseData]);
                return false;
            }

            Log::error("Failed to send SMS to {$phone}", ['status' => $response->status()]);
            return false;
        } catch (\Exception $e) {
            Log::error("Exception sending SMS to {$phone}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Format phone number for Ghana (0XXXXXXXXX format).
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 3) === '233') {
            return '0' . substr($phone, 3);
        }

        if (substr($phone, 0, 1) === '0') {
            return $phone;
        }

        return '0' . $phone;
    }
}
