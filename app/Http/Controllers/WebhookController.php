<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyPaymentJob;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Paystack webhook.
     */
    public function paystack(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');

        if (!$this->verifyPaystackSignature($request->getContent(), $signature)) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        // Handle charge.success event
        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;

            if ($reference) {
                // Dispatch job to verify payment
                VerifyPaymentJob::dispatch($reference);
            }
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }

    /**
     * Handle Hubtel webhook.
     */
    public function hubtel(Request $request)
    {
        Log::info('Hubtel webhook received', $request->all());

        $reference = $request->input('ClientReference');
        $status = $request->input('Status');

        if ($reference && $status === 'Success') {
            // Dispatch job to verify payment
            VerifyPaymentJob::dispatch($reference);
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }

    /**
     * Verify Paystack webhook signature.
     */
    private function verifyPaystackSignature(string $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $secretKey = config('services.paystack.secret_key');
        $computedSignature = hash_hmac('sha512', $payload, $secretKey);

        return hash_equals($computedSignature, $signature);
    }
}
