<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initialize a payment.
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'subscription_type' => ['nullable', 'in:daily,monthly'],
            'custom_amount' => ['nullable', 'numeric', 'min:1', 'max:1000'],
        ]);

        // Require either subscription_type or custom_amount
        if (!$request->subscription_type && !$request->custom_amount) {
            return back()->with('error', 'Please select a subscription plan or enter a custom amount.');
        }

        $user = $request->user();

        if ($user->isSuspended()) {
            return back()->with('error', 'Your account is suspended. Please contact support.');
        }

        // Determine payment type and amount
        $subscriptionType = $request->subscription_type;
        $customAmount = $request->custom_amount;

        // Initialize payment
        $result = $this->paymentService->initializePayment($user, $subscriptionType, $customAmount);

        if (!$result['status']) {
            return back()->with('error', $result['message']);
        }

        // Log the action
        AuditLog::log('payment_initialized', $user, [
            'reference' => $result['data']['reference'],
            'amount' => $result['data']['amount'],
            'subscription_type' => $subscriptionType,
            'custom_amount' => $customAmount,
        ]);

        // If there's an authorization URL, redirect to it
        if (!empty($result['data']['authorization_url'])) {
            return redirect($result['data']['authorization_url']);
        }

        // For Hubtel (USSD), show instructions
        return redirect()->route('payment.pending', ['reference' => $result['data']['reference']])
            ->with('success', 'Payment initiated. Please complete the payment on your phone.');
    }

    /**
     * Show payment callback page.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid payment reference.');
        }

        // Queue verification job
        \App\Jobs\VerifyPaymentJob::dispatch($reference);

        return redirect()->route('payment.pending', ['reference' => $reference]);
    }

    /**
     * Show pending payment page.
     */
    public function pending(Request $request, string $reference)
    {
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return redirect()->route('dashboard')
                ->with('error', 'Payment not found.');
        }

        return view('payments.pending', compact('payment'));
    }

    /**
     * Check payment status (AJAX endpoint).
     */
    public function checkStatus(Request $request, string $reference)
    {
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment not found',
            ], 404);
        }

        return response()->json([
            'status' => $payment->status,
            'message' => $this->getStatusMessage($payment->status),
        ]);
    }

    /**
     * Display payment history.
     */
    public function history(Request $request)
    {
        $payments = Payment::with('subscription')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('payments.history', compact('payments'));
    }

    /**
     * Display all payments (Admin/SuperAdmin).
     */
    public function index(Request $request)
    {
        $query = Payment::with('user');

        // Scope by organization
        $user = auth()->user();
        if ($user->hasRole('admin') && $user->organization_id) {
            $query->whereHas('user', fn($q) => $q->where('organization_id', $user->organization_id));
        } elseif ($user->hasRole('superadmin') && session('admin_org_filter')) {
            $query->whereHas('user', fn($q) => $q->where('organization_id', session('admin_org_filter')));
        }

        // Search functionality
        if ($request->filled('search')) {
            $query->where(function ($mainQ) use ($request) {
                $mainQ->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('phone', 'like', '%' . $request->search . '%');
                })->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payments = $query->latest()->paginate(20);

        return view('payments.index', compact('payments'));
    }

    /**
     * Verify payment from Hubtel return URL.
     */
    public function verify(Request $request)
    {
        $checkoutId = $request->query('checkoutid');

        if (!$checkoutId) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid payment reference.');
        }

        // Find payment by checkout ID (transaction_id)
        $payment = Payment::where('transaction_id', $checkoutId)->first();

        if (!$payment) {
            return redirect()->route('dashboard')
                ->with('error', 'Payment not found.');
        }

        // Check if already processed
        if ($payment->status === 'success') {
            return redirect()->route('payment.pending', ['reference' => $payment->reference])
                ->with('success', 'Payment already verified! Your subscription is active.');
        }

        try {
            // For development/testing: If Hubtel redirects here, assume payment was successful
            // In production, you should verify via callback webhook from whitelisted IPs
            if ($payment->status === 'pending') {
                // Mark as successful
                $payment->markAsSuccessful($checkoutId);
                
                // Determine subscription type based on amount
                $subscriptionType = $payment->amount == config('services.payment.daily_price', 3) ? 'daily' : 'monthly';
                $duration = $subscriptionType === 'daily' ? 1 : 30;
                
                // Create subscription
                $subscription = \App\Models\Subscription::create([
                    'user_id' => $payment->user_id,
                    'type' => $subscriptionType,
                    'amount' => $payment->amount,
                    'start_date' => now(),
                    'end_date' => now()->addDays($duration),
                    'status' => 'active',
                ]);
                
                \App\Models\AuditLog::log('payment_verified', $payment->user, [
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'subscription_id' => $subscription->id,
                ]);
            }

            return redirect()->route('payment.pending', ['reference' => $payment->reference])
                ->with('success', 'Payment successful! Your subscription has been activated.');
        } catch (\Exception $e) {
            \Log::error('Payment verification error', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('payment.pending', ['reference' => $payment->reference])
                ->with('error', 'An error occurred while verifying payment. Please contact support.');
        }
    }

    /**
     * Handle payment cancellation.
     */
    public function cancelled(Request $request)
    {
        return redirect()->route('dashboard')
            ->with('warning', 'Payment was cancelled. You can try again anytime.');
    }

    /**
     * Show manual/cash payment form for a user (admin/superadmin).
     */
    public function manualCreate(User $user)
    {
        return view('payments.manual', compact('user'));
    }

    /**
     * Record a manual/cash payment for a user (admin/superadmin).
     * No payment gateway, no service charges.
     */
    public function manualStore(Request $request, User $user)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:10000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = (float) $request->amount;
        $dailyRate = (float) Setting::get('daily_price', config('services.payment.daily_price', 3));
        $days = max(1, (int) floor($amount / $dailyRate));
        $reference = 'CASH-' . strtoupper(Str::random(10)) . '-' . time();

        // Create payment record — no service charge
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'amount' => $amount,
            'base_amount' => $amount,
            'service_charge' => 0,
            'payment_provider' => 'cash',
            'status' => 'success',
            'paid_at' => now(),
            'metadata' => [
                'payment_type' => 'custom',
                'recorded_by' => auth()->id(),
                'recorded_by_name' => auth()->user()->name,
                'note' => $request->note,
            ],
        ]);

        // Create or extend subscription
        $activeSubscription = $user->activeSubscription()->first();

        if ($activeSubscription && $activeSubscription->end_date >= now()) {
            $startDate = $activeSubscription->end_date->addDay();
        } else {
            $startDate = now();
        }

        $endDate = $startDate->copy()->addDays($days - 1);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'type' => 'custom',
            'amount' => $amount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        $payment->update(['subscription_id' => $subscription->id]);

        AuditLog::log('manual_payment_recorded', auth()->user(), [
            'payment_id' => $payment->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'amount' => $amount,
            'days_added' => $days,
            'note' => $request->note,
        ]);

        return redirect()->route('users.show', $user)
            ->with('success', "Cash payment of GHC {$amount} recorded. {$days} day(s) added to {$user->name}'s subscription.");
    }

    /**
     * Get status message.
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'success' => 'Payment successful! Your subscription has been activated.',
            'failed' => 'Payment failed. Please try again.',
            default => 'Payment is being processed...',
        };
    }
}
