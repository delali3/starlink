<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubtelProvider implements PaymentProviderInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $merchantAccountNumber;
    private string $apiUrl;
    private string $callbackUrl;

    public function __construct()
    {
        $this->clientId = Setting::get('hubtel_client_id', config('services.hubtel.client_id'));
        $this->clientSecret = Setting::get('hubtel_client_secret', config('services.hubtel.client_secret'));
        $this->merchantAccountNumber = Setting::get('hubtel_merchant_account_number', config('services.hubtel.merchant_account_number'));
        $this->apiUrl = Setting::get('hubtel_api_url', config('services.hubtel.api_url'));
        $this->callbackUrl = Setting::get('hubtel_callback_url', config('services.hubtel.callback_url'));
    }

    /**
     * Initialize payment transaction.
     *
     * @param array $data
     * @return array
     */
    public function initializePayment(array $data): array
    {
        try {
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for local development
            ])->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'totalAmount' => $data['amount'],
                'description' => 'Subscription Payment - ' . $data['subscription_type'],
                'callbackUrl' => $this->callbackUrl,
                'returnUrl' => config('app.url') . '/payments/verify',
                'merchantAccountNumber' => $this->merchantAccountNumber,
                'cancellationUrl' => config('app.url') . '/payments/cancelled',
                'clientReference' => $data['reference'],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['responseCode']) && $responseData['responseCode'] === '0000') {
                    $responseDetails = $responseData['data'] ?? [];
                    
                    return [
                        'status' => true,
                        'message' => 'Payment initialized successfully',
                        'data' => [
                            'authorization_url' => $responseDetails['checkoutUrl'] ?? null,
                            'reference' => $responseDetails['clientReference'] ?? $data['reference'],
                            'transaction_id' => $responseDetails['checkoutId'] ?? null,
                            'checkout_direct_url' => $responseDetails['checkoutDirectUrl'] ?? null,
                        ],
                    ];
                }
            }

            Log::error('Hubtel initialization failed', [
                'response' => $response->json(),
            ]);

            return [
                'status' => false,
                'message' => 'Payment initialization failed',
            ];
        } catch (\Exception $e) {
            Log::error('Hubtel initialization exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while initializing payment',
            ];
        }
    }

    /**
     * Verify payment transaction.
     *
     * @param string $reference
     * @return array
     */
    public function verifyPayment(string $reference): array
    {
        try {
            // Use Hubtel Transaction Status API
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for local development
            ])->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            ])->get('https://api-txnstatus.hubtel.com/transactions/' . $this->merchantAccountNumber . '/status', [
                'clientReference' => $reference,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Hubtel verification response', [
                    'reference' => $reference,
                    'response' => $result,
                ]);

                if (isset($result['responseCode']) && $result['responseCode'] === '0000' && isset($result['data'])) {
                    $data = $result['data'];
                    $isPaid = strtolower($data['status'] ?? '') === 'paid';

                    return [
                        'status' => $isPaid,
                        'message' => $isPaid ? 'Payment verified successfully' : 'Payment not yet completed',
                        'data' => [
                            'reference' => $data['clientReference'] ?? $reference,
                            'amount' => $data['amount'] ?? 0,
                            'status' => $isPaid ? 'success' : 'pending',
                            'paid_at' => $data['date'] ?? now(),
                            'transaction_id' => $data['transactionId'] ?? null,
                            'external_transaction_id' => $data['externalTransactionId'] ?? null,
                            'payment_method' => $data['paymentMethod'] ?? null,
                            'charges' => $data['charges'] ?? 0,
                            'amount_after_charges' => $data['amountAfterCharges'] ?? 0,
                            'metadata' => [],
                        ],
                    ];
                }
            }

            Log::error('Hubtel verification failed', [
                'reference' => $reference,
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'status' => false,
                'message' => 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('Hubtel verification exception', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while verifying payment',
            ];
        }
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'hubtel';
    }

    /**
     * Format phone number for Hubtel (233XXXXXXXXX format).
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 233
        if (substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1);
        }

        // If doesn't start with 233, prepend it
        if (substr($phone, 0, 3) !== '233') {
            $phone = '233' . $phone;
        }

        return $phone;
    }
}
