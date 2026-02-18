@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}!</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage your subscription and payments</p>
    </div>

    <!-- Balance Summary -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-4 sm:p-6 text-white">
        <h2 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Account Balance</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            <div class="bg-white bg-opacity-20 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm opacity-90">Total Paid</div>
                <div class="text-2xl sm:text-3xl font-bold mt-1">GHC {{ number_format($balanceInfo['total_paid'], 2) }}</div>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm opacity-90">Expected Amount</div>
                <div class="text-2xl sm:text-3xl font-bold mt-1">GHC {{ number_format($balanceInfo['expected_amount'], 2) }}</div>
            </div>
            <div class="bg-white bg-opacity-20 rounded-lg p-3 sm:p-4">
                <div class="text-xs sm:text-sm opacity-90">
                    @if($balanceInfo['has_credit'])
                        Credit Balance
                    @else
                        Amount Owed
                    @endif
                </div>
                <div class="text-2xl sm:text-3xl font-bold mt-1 {{ $balanceInfo['has_credit'] ? 'text-green-200' : 'text-red-200' }}">
                    GHC {{ number_format(abs($balanceInfo['balance']), 2) }}
                </div>
                @if($balanceInfo['has_credit'])
                    <div class="text-xs sm:text-sm mt-1 opacity-90">{{ $balanceInfo['days_remaining'] }} weekday{{ $balanceInfo['days_remaining'] !== 1 ? 's' : '' }} remaining</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Current Month Payment Status -->
    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
            <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            {{ now()->format('F Y') }} Payment Status
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="bg-blue-50 rounded-lg p-3 sm:p-4 border border-blue-200">
                <div class="text-xs sm:text-sm text-blue-700 font-medium">Expected This Month</div>
                <div class="text-xl sm:text-2xl font-bold text-blue-900 mt-1">GHC {{ number_format($currentMonthInfo['expected'], 2) }}</div>
                <div class="text-xs text-blue-600 mt-1">Based on weekdays</div>
            </div>
            
            <div class="bg-green-50 rounded-lg p-3 sm:p-4 border border-green-200">
                <div class="text-xs sm:text-sm text-green-700 font-medium">Paid This Month</div>
                <div class="text-xl sm:text-2xl font-bold text-green-900 mt-1">GHC {{ number_format($currentMonthInfo['paid'], 2) }}</div>
                <div class="text-xs text-green-600 mt-1">
                    {{ $currentMonthInfo['paid'] >= $currentMonthInfo['expected'] ? '✓ Fully paid' : 'Partial payment' }}
                </div>
            </div>
            
            <div class="bg-{{ $currentMonthInfo['left_to_pay'] > 0 ? 'orange' : 'gray' }}-50 rounded-lg p-3 sm:p-4 border border-{{ $currentMonthInfo['left_to_pay'] > 0 ? 'orange' : 'gray' }}-200">
                <div class="text-xs sm:text-sm text-{{ $currentMonthInfo['left_to_pay'] > 0 ? 'orange' : 'gray' }}-700 font-medium">Left to Pay</div>
                <div class="text-xl sm:text-2xl font-bold text-{{ $currentMonthInfo['left_to_pay'] > 0 ? 'orange' : 'gray' }}-900 mt-1">GHC {{ number_format($currentMonthInfo['left_to_pay'], 2) }}</div>
                <div class="text-xs text-{{ $currentMonthInfo['left_to_pay'] > 0 ? 'orange' : 'gray' }}-600 mt-1">
                    {{ $currentMonthInfo['left_to_pay'] > 0 ? 'Payment needed' : 'All caught up!' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Skipped/Unpaid Months -->
    @if(count($skippedMonths) > 0)
    <div class="bg-red-50 border-l-4 border-red-400 rounded-lg shadow p-4 sm:p-6">
        <div class="flex items-start mb-3 sm:mb-4">
            <svg class="h-5 w-5 sm:h-6 sm:w-6 text-red-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h2 class="text-base sm:text-lg font-semibold text-red-900">Unpaid/Partial Months ({{ count($skippedMonths) }})</h2>
                <p class="text-xs sm:text-sm text-red-700 mt-1">These months have outstanding balances</p>
            </div>
        </div>
        
        <div class="space-y-2 sm:space-y-3">
            @foreach($skippedMonths as $month)
            <div class="bg-white rounded-lg p-3 sm:p-4 border border-red-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900 text-sm sm:text-base">{{ $month['month'] }}</div>
                        <div class="text-xs sm:text-sm text-gray-600 mt-1">
                            <span class="inline-block mr-3">Expected: <strong class="text-gray-900">GHC {{ number_format($month['expected'], 2) }}</strong></span>
                            <span class="inline-block">Paid: <strong class="text-green-600">GHC {{ number_format($month['paid'], 2) }}</strong></span>
                        </div>
                    </div>
                    <div class="text-right sm:text-left">
                        <div class="text-xs text-red-600 font-medium">Amount Owed</div>
                        <div class="text-lg sm:text-xl font-bold text-red-700">GHC {{ number_format($month['owed'], 2) }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-red-200">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div class="text-sm text-red-800">
                    <strong>Total Owed from Past Months:</strong>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-red-900">
                    GHC {{ number_format(array_sum(array_column($skippedMonths, 'owed')), 2) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Subscription Status -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Subscription Status</h2>
            
            @if($subscriptionStatus['is_active'])
            <div class="bg-green-50 border-l-4 border-green-400 p-3 sm:p-4 rounded mb-3 sm:mb-4">
                <div class="flex items-start sm:items-center justify-between flex-wrap gap-2 sm:gap-4">
                    <div class="w-full">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 text-green-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm sm:text-base text-green-700 font-semibold">Active Subscription</span>
                        </div>
                        <p class="text-xs sm:text-sm text-green-600 mt-1 ml-6 sm:ml-7">
                            @if($balanceInfo['has_credit'])
                                You have <strong>{{ $balanceInfo['days_remaining'] }} weekday{{ $balanceInfo['days_remaining'] !== 1 ? 's' : '' }}</strong> of credit remaining
                                @if($balanceInfo['credit_expiry_date'])
                                    · Access until {{ $balanceInfo['credit_expiry_date']->format('M d, Y') }}
                                @endif
                            @else
                                <span class="font-medium capitalize">{{ $subscriptionStatus['type'] }}</span> plan
                                · Expires on {{ $subscriptionStatus['end_date']->format('M d, Y') }}
                                · {{ $subscriptionStatus['days_remaining'] }} {{ Str::plural('day', $subscriptionStatus['days_remaining']) }} remaining
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 sm:p-4 rounded mb-3 sm:mb-4">
                <div class="flex items-center">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-yellow-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm sm:text-base text-yellow-700 font-semibold">No Active Subscription</span>
                </div>
                <p class="text-xs sm:text-sm text-yellow-600 mt-1 ml-6 sm:ml-7">Subscribe now to access premium features</p>
            </div>
            @endif

            <!-- Payment Options -->
            <div class="grid grid-cols-1 gap-4 sm:gap-6 mt-4 sm:mt-6">
                <!-- Custom Amount Payment -->
                <div class="border-2 border-purple-200 rounded-lg p-4 sm:p-6 bg-purple-50">
                    <div class="mb-3 sm:mb-4">
                        <h3 class="text-lg sm:text-xl font-bold text-purple-900 mb-1 sm:mb-2">Custom Payment</h3>
                        <p class="text-purple-700 text-xs sm:text-sm">Pay any amount to add credit to your account (GHC 3 = 1 weekday)</p>
                    </div>
                    <form action="{{ route('payment.initialize') }}" method="POST" class="space-y-3 sm:space-y-4" x-data="{ amount: '' }">
                        @csrf
                        <div>
                            <label for="custom_amount" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Enter Amount (GHC)</label>
                            <input
                                type="number"
                                name="custom_amount"
                                id="custom_amount"
                                x-model="amount"
                                step="0.01"
                                min="1"
                                max="1000"
                                class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Enter amount (e.g., 15.00)"
                                required>
                            <div x-show="amount > 0" x-cloak class="mt-2">
                                <p class="text-xs sm:text-sm text-gray-600">
                                    This will add approximately <strong x-text="Math.floor(amount / 3)"></strong>
                                    <span x-text="Math.floor(amount / 3) === 1 ? 'weekday' : 'weekdays'"></span> to your account
                                </p>
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-sm sm:text-base font-semibold py-2 sm:py-3 px-4 rounded-lg transition">
                            Pay Custom Amount
                        </button>
                    </form>
                </div>

                <!-- Quick Payment Options -->
                <div>
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Quick Payment Options</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <!-- Daily Plan -->
                        <div class="border-2 border-green-200 rounded-lg p-4 sm:p-6 hover:border-green-400 transition">
                            <div class="text-center">
                                <div class="text-3xl sm:text-4xl font-bold text-green-600 mb-1 sm:mb-2">GHC 3</div>
                                <div class="text-sm sm:text-base text-gray-600 mb-1">Daily Subscription</div>
                                <div class="text-xs sm:text-sm text-gray-500 mb-3 sm:mb-4">Valid for 1 weekday</div>
                                <form action="{{ route('payment.initialize') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="subscription_type" value="daily">
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-sm sm:text-base font-semibold py-2 sm:py-3 px-4 rounded-lg transition">
                                        Pay Daily
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Monthly Plan -->
                        <div class="border-2 border-blue-200 rounded-lg p-4 sm:p-6 hover:border-blue-400 transition">
                            <div class="text-center">
                                <div class="text-3xl sm:text-4xl font-bold text-blue-600 mb-1 sm:mb-2">GHC 60</div>
                                <div class="text-sm sm:text-base text-gray-600 mb-1">Monthly Subscription</div>
                                <div class="text-xs sm:text-sm text-gray-500 mb-1">Valid for 20 weekdays</div>
                                <div class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded mb-2 sm:mb-3">
                                    Save GHC 30
                                </div>
                                <form action="{{ route('payment.initialize') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="subscription_type" value="monthly">
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm sm:text-base font-semibold py-2 sm:py-3 px-4 rounded-lg transition">
                                        Pay Monthly
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 sm:p-6">
            <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Recent Payments</h2>
            
            @if($payments->count() > 0)
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($payments as $payment)
                        <tr>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                <div class="sm:hidden">{{ $payment->created_at->format('M d') }}</div>
                                <div class="hidden sm:block">{{ $payment->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-medium text-gray-900">
                                <div class="sm:hidden">₵{{ number_format($payment->base_amount ?? $payment->amount, 0) }}</div>
                                <div class="hidden sm:block">GHC {{ number_format($payment->base_amount ?? $payment->amount, 2) }}</div>
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $payment->reference }}
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
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 sm:mt-4">
                {{ $payments->links() }}
            </div>
            @else
            <p class="text-sm sm:text-base text-gray-500 text-center py-6 sm:py-8">No payment history yet</p>
            @endif
        </div>
    </div>
</div>
@endsection
