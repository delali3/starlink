@extends('layouts.app')

@section('title', 'Payment History')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Payment History</h1>
                <p class="text-sm sm:text-base text-gray-600 mt-1">View all your payment transactions</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 sm:px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm sm:text-base font-semibold rounded-lg transition">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 sm:p-6">
            @if($payments->count() > 0)
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                <div>{{ $payment->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500 hidden sm:block">{{ $payment->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-medium text-gray-900">
                                <div class="sm:hidden">₵{{ number_format($payment->base_amount ?? $payment->amount, 0) }}</div>
                                <div class="hidden sm:block">
                                    GHC {{ number_format($payment->base_amount ?? $payment->amount, 2) }}
                                </div>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $payment->reference }}
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                @if($payment->metadata && isset($payment->metadata['payment_type']))
                                    <span class="capitalize">{{ $payment->metadata['payment_type'] }}</span>
                                @elseif($payment->subscription)
                                    <span class="capitalize">{{ $payment->subscription->type }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-700 capitalize">
                                {{ $payment->payment_provider }}
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                @if($payment->status === 'success')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Success
                                </span>
                                @elseif($payment->status === 'pending')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                                @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Failed
                                </span>
                                <form action="{{ route('payment.initialize') }}" method="POST" class="inline ml-1">
                                    @csrf
                                    <input type="hidden" name="custom_amount" value="{{ $payment->base_amount ?? $payment->amount }}">
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Retry</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 sm:mt-6">
                {{ $payments->links() }}
            </div>

            <!-- Summary -->
            <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                    <div class="bg-green-50 rounded-lg p-3 sm:p-4">
                        <div class="text-xs sm:text-sm text-green-700 font-medium">Total Paid</div>
                        <div class="text-xl sm:text-2xl font-bold text-green-900 mt-1">
                            GHC {{ number_format($payments->where('status', 'success')->sum(fn($p) => $p->base_amount ?? $p->amount), 2) }}
                        </div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-3 sm:p-4">
                        <div class="text-xs sm:text-sm text-blue-700 font-medium">Successful Payments</div>
                        <div class="text-xl sm:text-2xl font-bold text-blue-900 mt-1">
                            {{ $payments->where('status', 'success')->count() }}
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                        <div class="text-xs sm:text-sm text-gray-700 font-medium">Total Transactions</div>
                        <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">
                            {{ $payments->total() }}
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-8 sm:py-12">
                <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No payment history</h3>
                <p class="mt-1 text-xs sm:text-sm text-gray-500">You haven't made any payments yet.</p>
                <div class="mt-4 sm:mt-6">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm sm:text-base font-semibold rounded-lg transition">
                        Make Your First Payment
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
