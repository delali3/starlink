@extends('layouts.app')

@section('title', 'Compose Bulk SMS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="mb-6">
        <a href="{{ route('sms.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            &larr; Back to SMS History
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Compose Bulk SMS</h2>

        <form method="POST" action="{{ route('sms.store') }}" x-data="{ message: '' }">
            @csrf

            <div class="space-y-6">
                @if(auth()->user()->hasRole('superadmin') && isset($organizations))
                <div>
                    <label for="organization_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Target Organization</label>
                    <select id="organization_id" name="organization_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $org)
                        <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                    @error('organization_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipient Filter - Status</label>
                    <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Users</option>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active Users Only</option>
                        <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended Users Only</option>
                    </select>
                </div>

                <div>
                    <label for="subscription_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipient Filter - Subscription</label>
                    <select id="subscription_status" name="subscription_status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Subscriptions</option>
                        <option value="active" {{ old('subscription_status') === 'active' ? 'selected' : '' }}>With Active Subscription</option>
                        <option value="expired" {{ old('subscription_status') === 'expired' ? 'selected' : '' }}>With Expired/No Subscription</option>
                    </select>
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                    <textarea id="message" name="message" rows="5" x-model="message" required maxlength="480"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('message') border-red-500 @enderror"
                        placeholder="Type your message here...">{{ old('message') }}</textarea>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-gray-500" x-text="message.length + '/480 characters'"></p>
                        <p class="text-xs text-gray-500" x-text="Math.ceil(message.length / 160) + ' SMS part(s)'"></p>
                    </div>
                    @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-3 pt-4">
                    <a href="{{ route('sms.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition" onclick="return confirm('Are you sure you want to send this SMS to the selected recipients?')">
                        Send SMS
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
