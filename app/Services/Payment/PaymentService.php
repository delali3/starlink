<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    private PaymentProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->getProvider();
    }

    /**
     * Get the configured payment provider.
     *
     * @return PaymentProviderInterface
     */
    private function getProvider(): PaymentProviderInterface
    {
        $providerName = config('services.payment.provider', 'paystack');

        return match ($providerName) {
            'hubtel' => new HubtelProvider(),
            default => new PaystackProvider(),
        };
    }

    /**
     * Initialize a payment.
     *
     * @param User $user
     * @param string|null $subscriptionType
     * @param float|null $customAmount
     * @return array
     */
    public function initializePayment(User $user, ?string $subscriptionType = null, ?float $customAmount = null): array
    {
        // Determine amount
        if ($customAmount) {
            // Custom amount payment
            $amount = $customAmount;
            $type = 'custom';
        } elseif ($subscriptionType) {
            // Validate subscription type
            if (!in_array($subscriptionType, ['daily', 'monthly'])) {
                return [
                    'status' => false,
                    'message' => 'Invalid subscription type',
                ];
            }

            // Get amount based on subscription type
            $amount = $subscriptionType === 'daily'
                ? config('services.payment.daily_price', 3)
                : config('services.payment.monthly_price', 60);
            $type = $subscriptionType;
        } else {
            return [
                'status' => false,
                'message' => 'Please provide either subscription type or custom amount',
            ];
        }

        // Generate unique reference
        $reference = $this->generateReference();

        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'amount' => $amount,
            'payment_provider' => $this->provider->getProviderName(),
            'status' => 'pending',
            'metadata' => [
                'payment_type' => $type,
                'subscription_type' => $subscriptionType,
            ],
        ]);

        // Initialize payment with provider
        $result = $this->provider->initializePayment([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'amount' => $amount,
            'reference' => $reference,
            'subscription_type' => $subscriptionType ?? 'custom',
            'callback_url' => route('payment.callback'),
        ]);

        if ($result['status']) {
            // Update payment with transaction ID
            $payment->update([
                'transaction_id' => $result['data']['transaction_id'] ?? null,
            ]);

            return [
                'status' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'reference' => $reference,
                    'authorization_url' => $result['data']['authorization_url'] ?? null,
                    'amount' => $amount,
                ],
            ];
        }

        // Mark payment as failed
        $payment->markAsFailed();

        return $result;
    }

    /**
     * Verify a payment and create subscription if successful.
     *
     * @param string $reference
     * @return array
     */
    public function verifyPayment(string $reference): array
    {
        // Find payment record
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return [
                'status' => false,
                'message' => 'Payment not found',
            ];
        }

        // Don't verify already successful payments
        if ($payment->isSuccessful()) {
            return [
                'status' => true,
                'message' => 'Payment already verified',
                'data' => ['payment' => $payment],
            ];
        }

        // Verify with provider
        $result = $this->provider->verifyPayment($reference);

        if ($result['status'] && $result['data']['status'] === 'success') {
            // Update payment record
            $payment->markAsSuccessful($result['data']['transaction_id']);

            // Create or extend subscription
            $subscription = $this->createOrExtendSubscription($payment);

            return [
                'status' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'subscription' => $subscription,
                ],
            ];
        }

        // Mark as failed if verification failed
        $payment->markAsFailed();

        return $result;
    }

    /**
     * Create or extend subscription based on payment.
     *
     * @param Payment $payment
     * @return Subscription
     */
    private function createOrExtendSubscription(Payment $payment): Subscription
    {
        $user = $payment->user;
        $paymentType = $payment->metadata['payment_type'] ?? 'daily';
        
        // Determine subscription type and days
        if ($paymentType === 'custom') {
            // For custom payments, calculate days based on daily rate
            $dailyRate = config('services.payment.daily_price', 3);
            $days = max(1, (int) floor($payment->amount / $dailyRate));
            $subscriptionType = 'custom';
        } elseif ($paymentType === 'monthly') {
            $subscriptionType = 'monthly';
            $days = 30;
        } else {
            $subscriptionType = 'daily';
            $days = 1;
        }

        // Check if user has an active subscription
        $activeSubscription = $user->activeSubscription()->first();

        if ($activeSubscription && $activeSubscription->end_date >= now()) {
            // Extend existing subscription
            $startDate = $activeSubscription->end_date->addDay();
            $endDate = $startDate->copy()->addDays($days - 1);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => $subscriptionType,
                'amount' => $payment->amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
            ]);

            // Mark old subscription as expired if it's a different type
            if ($activeSubscription->type !== $subscriptionType) {
                $activeSubscription->markAsExpired();
            }
        } else {
            // Create new subscription starting from today
            $startDate = now();
            $endDate = $startDate->copy()->addDays($days - 1);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => $subscriptionType,
                'amount' => $payment->amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
            ]);
        }

        // Link payment to subscription
        $payment->update(['subscription_id' => $subscription->id]);

        return $subscription;
    }

    /**
     * Generate a unique payment reference.
     *
     * @return string
     */
    private function generateReference(): string
    {
        return 'GHL-' . strtoupper(Str::random(12)) . '-' . time();
    }

    /**
     * Get the current provider instance.
     *
     * @return PaymentProviderInterface
     */
    public function getProviderInstance(): PaymentProviderInterface
    {
        return $this->provider;
    }
}
