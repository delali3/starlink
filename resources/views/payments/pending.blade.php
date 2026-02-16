@extends('layouts.app')

@section('title', 'Payment Pending')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center" x-data="paymentChecker('{{ $payment->reference }}', '{{ $payment->status }}')">
        <div x-show="status === 'pending'" class="animate-pulse">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-yellow-100 mb-4">
                <svg class="h-12 w-12 text-yellow-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Processing Payment...</h2>
            <p class="text-gray-600 mb-4">Please wait while we verify your payment</p>
            <div class="text-sm text-gray-500 space-y-1">
                <p>Reference: <span class="font-mono font-medium">{{ $payment->reference }}</span></p>
                <p>Amount: <span class="font-semibold text-gray-900">GHC {{ number_format($payment->amount, 2) }}</span></p>
            </div>
        </div>

        <div x-show="status === 'success'" style="display: none;">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-4">
                <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-green-600 mb-2">Payment Successful!</h2>
            <p class="text-gray-600 mb-6" x-text="message"></p>
            <a href="{{ route('dashboard') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                Go to Dashboard
            </a>
        </div>

        <div x-show="status === 'failed'" style="display: none;">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-red-100 mb-4">
                <svg class="h-12 w-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-red-600 mb-2">Payment Failed</h2>
            <p class="text-gray-600 mb-6" x-text="message"></p>
            <a href="{{ route('dashboard') }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                Try Again
            </a>
        </div>
    </div>
</div>

<script>
function paymentChecker(reference, initialStatus) {
    return {
        status: initialStatus,
        message: '',
        interval: null,
        
        init() {
            if (this.status === 'pending') {
                this.checkStatus();
                this.interval = setInterval(() => {
                    this.checkStatus();
                }, 3000); // Check every 3 seconds
            }
        },
        
        async checkStatus() {
            try {
                const response = await fetch(`/api/payment/${reference}/status`);
                const data = await response.json();
                
                this.status = data.status;
                this.message = data.message;
                
                if (data.status !== 'pending') {
                    clearInterval(this.interval);
                }
            } catch (error) {
                console.error('Error checking payment status:', error);
            }
        }
    }
}
</script>
@endsection
