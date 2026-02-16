<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackProvider implements PaymentProviderInterface
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'email' => $data['email'] ?? $data['phone'] . '@ghlinks.com',
                'amount' => $data['amount'] * 100, // Convert to pesewas
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'],
                'metadata' => [
                    'user_id' => $data['user_id'],
                    'phone' => $data['phone'],
                    'subscription_type' => $data['subscription_type'],
                ],
            ]);

            if ($response->successful() && $response->json('status')) {
                return [
                    'status' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'authorization_url' => $response->json('data.authorization_url'),
                        'access_code' => $response->json('data.access_code'),
                        'reference' => $response->json('data.reference'),
                    ],
                ];
            }

            Log::error('Paystack initialization failed', [
                'response' => $response->json(),
            ]);

            return [
                'status' => false,
                'message' => $response->json('message', 'Payment initialization failed'),
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initialization exception', [
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);

            if ($response->successful() && $response->json('status')) {
                $data = $response->json('data');

                return [
                    'status' => true,
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'reference' => $data['reference'],
                        'amount' => $data['amount'] / 100, // Convert from pesewas
                        'status' => $data['status'],
                        'paid_at' => $data['paid_at'] ?? now(),
                        'transaction_id' => $data['id'],
                        'metadata' => $data['metadata'] ?? [],
                    ],
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message', 'Payment verification failed'),
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verification exception', [
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
        return 'paystack';
    }

    /**
     * Get the public key.
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
