<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class VerifyPaymentJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry after 10s, 30s, 60s

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $reference
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        Log::info('Verifying payment', ['reference' => $this->reference]);

        $result = $paymentService->verifyPayment($this->reference);

        if ($result['status']) {
            Log::info('Payment verified successfully', [
                'reference' => $this->reference,
                'payment_id' => $result['data']['payment']->id ?? null,
            ]);

            // Log the successful payment
            if (isset($result['data']['payment'])) {
                AuditLog::log('payment_verified', $result['data']['payment']->user, [
                    'payment_id' => $result['data']['payment']->id,
                    'reference' => $this->reference,
                    'amount' => $result['data']['payment']->amount,
                ]);
            }
        } else {
            Log::warning('Payment verification failed', [
                'reference' => $this->reference,
                'message' => $result['message'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment verification job failed', [
            'reference' => $this->reference,
            'error' => $exception->getMessage(),
        ]);
    }
}
