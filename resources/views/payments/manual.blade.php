@extends('layouts.app')

@section('title', 'Record Cash Payment')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('users.show', $user) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to {{ $user->name }}
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-2">Record Cash Payment</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Record a physical cash payment for this user</p>
    </div>

    <!-- User Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-lg">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="ml-4">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $user->name }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->phone }}</div>
            </div>
            <div class="ml-auto">
                @if($user->activeSubscription)
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active Sub</span>
                @else
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">No Active Sub</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" x-data="{ amount: '' }">
        <div class="flex items-center mb-5">
            <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Details</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">No gateway charges will be applied</p>
            </div>
        </div>

        <form method="POST" action="{{ route('payments.manual.store', $user) }}">
            @csrf

            <div class="space-y-5">
                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount Received (GHC) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" id="amount" x-model="amount" step="0.01" min="1" max="10000" required
                        value="{{ old('amount') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-lg @error('amount') border-red-500 @enderror"
                        placeholder="Enter cash amount received">
                    @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <!-- Days calculation -->
                    <div x-show="amount > 0" x-cloak class="mt-3 bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-green-700 dark:text-green-300">Days to be added:</span>
                            <span class="text-lg font-bold text-green-800 dark:text-green-200" x-text="Math.floor(amount / {{ \App\Models\Setting::get('daily_price', 3) }}) + ' day' + (Math.floor(amount / {{ \App\Models\Setting::get('daily_price', 3) }}) !== 1 ? 's' : '')"></span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-green-600 dark:text-green-400">Rate: GHC {{ number_format(\App\Models\Setting::get('daily_price', 3), 2) }} per day</span>
                            <span class="text-xs text-green-600 dark:text-green-400">No service charges</span>
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note (Optional)</label>
                    <textarea name="note" id="note" rows="2" maxlength="500"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('note') border-red-500 @enderror"
                        placeholder="e.g. Cash received at office">{{ old('note') }}</textarea>
                    @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Info box -->
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        This payment will be recorded immediately as successful. No payment gateway will be triggered and no service charges will be applied. The subscription days will be added to the user's account right away.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('users.show', $user) }}" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition inline-flex items-center" onclick="return confirm('Record cash payment of GHC ' + document.getElementById('amount').value + ' for {{ $user->name }}?')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Record Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
