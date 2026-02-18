<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentConfirmation;
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
     */
    private function getProvider(): PaymentProviderInterface
    {
        $providerName = Setting::get('payment_provider', config('services.payment.provider', 'hubtel'));

        return match ($providerName) {
            'paystack' => new PaystackProvider(),
            default => new HubtelProvider(),
        };
    }

    /**
     * Initialize a payment.
     */
    public function initializePayment(User $user, ?string $subscriptionType = null, ?float $customAmount = null): array
    {
        // Determine base amount
        if ($customAmount) {
            $baseAmount = $customAmount;
            $type = 'custom';
        } elseif ($subscriptionType) {
            if (!in_array($subscriptionType, ['daily', 'monthly'])) {
                return [
                    'status' => false,
                    'message' => 'Invalid subscription type',
                ];
            }

            $baseAmount = $subscriptionType === 'daily'
                ? (float) Setting::get('daily_price', config('services.payment.daily_price', 3))
                : (float) Setting::get('monthly_price', config('services.payment.monthly_price', 60));
            $type = $subscriptionType;
        } else {
            return [
                'status' => false,
                'message' => 'Please provide either subscription type or custom amount',
            ];
        }

        // Calculate service charge (2% on top)
        $chargePercent = (float) Setting::get('service_charge_percentage', 2);
        $serviceCharge = round($baseAmount * ($chargePercent / 100), 2);
        $totalAmount = $baseAmount + $serviceCharge;

        // Generate unique reference
        $reference = $this->generateReference();

        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'base_amount' => $baseAmount,
            'amount' => $totalAmount,
            'service_charge' => $serviceCharge,
            'payment_provider' => $this->provider->getProviderName(),
            'status' => 'pending',
            'metadata' => [
                'payment_type' => $type,
                'subscription_type' => $subscriptionType,
                'base_amount' => $baseAmount,
                'service_charge' => $serviceCharge,
                'service_charge_percentage' => $chargePercent,
            ],
        ]);

        // Initialize payment with provider (charge total including service charge)
        $result = $this->provider->initializePayment([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'amount' => $totalAmount,
            'reference' => $reference,
            'subscription_type' => $subscriptionType ?? 'custom',
            'callback_url' => route('payment.callback'),
        ]);

        if ($result['status']) {
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
                    'amount' => $totalAmount,
                    'base_amount' => $baseAmount,
                    'service_charge' => $serviceCharge,
                ],
            ];
        }

        $payment->markAsFailed();

        return $result;
    }

    /**
     * Verify a payment and create subscription if successful.
     */
    public function verifyPayment(string $reference): array
    {
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return [
                'status' => false,
                'message' => 'Payment not found',
            ];
        }

        if ($payment->isSuccessful()) {
            return [
                'status' => true,
                'message' => 'Payment already verified',
                'data' => ['payment' => $payment],
            ];
        }

        $result = $this->provider->verifyPayment($reference);

        if ($result['status'] && $result['data']['status'] === 'success') {
            $payment->markAsSuccessful($result['data']['transaction_id']);

            $subscription = $this->createOrExtendSubscription($payment);

            // Send email notification if user has email
            if ($payment->user && $payment->user->email) {
                $payment->user->notify(new PaymentConfirmation($payment));
            }

            return [
                'status' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'subscription' => $subscription,
                ],
            ];
        }

        $payment->markAsFailed();

        return $result;
    }

    /**
     * Create or extend subscription based on payment.
     * Uses base_amount (before service charge) for day calculation.
     */
    private function createOrExtendSubscription(Payment $payment): Subscription
    {
        $user = $payment->user;
        $paymentType = $payment->metadata['payment_type'] ?? 'daily';

        // Use base_amount for subscription calculation (exclude service charge)
        $subscriptionAmount = $payment->base_amount ?? $payment->amount;

        if ($paymentType === 'custom') {
            $dailyRate = (float) Setting::get('daily_price', config('services.payment.daily_price', 3));
            $days = max(1, (int) floor($subscriptionAmount / $dailyRate));
            $subscriptionType = 'custom';
        } elseif ($paymentType === 'monthly') {
            $subscriptionType = 'monthly';
            $days = 30;
        } else {
            $subscriptionType = 'daily';
            $days = 1;
        }

        $activeSubscription = $user->activeSubscription()->first();

        if ($activeSubscription && $activeSubscription->end_date >= now()) {
            $startDate = $activeSubscription->end_date->addDay();
            $endDate = $startDate->copy()->addDays($days - 1);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => $subscriptionType,
                'amount' => $subscriptionAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
            ]);

            if ($activeSubscription->type !== $subscriptionType) {
                $activeSubscription->markAsExpired();
            }
        } else {
            $startDate = now();
            $endDate = $startDate->copy()->addDays($days - 1);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => $subscriptionType,
                'amount' => $subscriptionAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
            ]);
        }

        $payment->update(['subscription_id' => $subscription->id]);

        return $subscription;
    }

    /**
     * Generate a unique payment reference.
     */
    private function generateReference(): string
    {
        return 'GHL-' . strtoupper(Str::random(12)) . '-' . time();
    }

    /**
     * Get the current provider instance.
     */
    public function getProviderInstance(): PaymentProviderInterface
    {
        return $this->provider;
    }
}
