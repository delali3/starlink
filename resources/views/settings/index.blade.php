@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ activeProvider: '{{ $settings->flatten()->firstWhere('key', 'payment_provider')?->value ?? 'hubtel' }}' }">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">System Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Manage application configuration</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        @foreach($settings as $group => $groupSettings)
        @php
            $groupIcon = match($group) {
                'payment' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
                'hubtel' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                'paystack' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                'sms' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>',
                'general' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
            };
            $groupColor = match($group) {
                'payment' => 'indigo',
                'hubtel' => 'orange',
                'paystack' => 'green',
                'sms' => 'blue',
                'general' => 'gray',
                default => 'gray',
            };
            $groupLabel = match($group) {
                'hubtel' => 'Hubtel Credentials',
                'paystack' => 'Paystack Credentials',
                default => ucwords(str_replace('_', ' ', $group)),
            };
        @endphp

        {{-- Show/hide Hubtel and Paystack sections based on selected provider --}}
        @if($group === 'hubtel')
        <div x-show="activeProvider === 'hubtel'" x-collapse x-cloak>
        @elseif($group === 'paystack')
        <div x-show="activeProvider === 'paystack'" x-collapse x-cloak>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex items-center mb-5">
                <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-{{ $groupColor }}-100 dark:bg-{{ $groupColor }}-900 flex items-center justify-center">
                    <svg class="h-5 w-5 text-{{ $groupColor }}-600 dark:text-{{ $groupColor }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $groupIcon !!}</svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $groupLabel }}</h2>
                    @if($group === 'hubtel')
                    <p class="text-sm text-gray-500 dark:text-gray-400">Configure your Hubtel payment gateway credentials</p>
                    @elseif($group === 'paystack')
                    <p class="text-sm text-gray-500 dark:text-gray-400">Configure your Paystack payment gateway credentials</p>
                    @elseif($group === 'payment')
                    <p class="text-sm text-gray-500 dark:text-gray-400">Subscription pricing and payment provider</p>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                @foreach($groupSettings as $setting)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <label for="setting_{{ $setting->key }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ ucwords(str_replace(['hubtel_', 'paystack_', '_'], ['', '', ' '], $setting->key)) }}
                        </label>
                        @if($setting->description)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $setting->description }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        @if($setting->key === 'payment_provider')
                        {{-- Special provider selector with cards --}}
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer" @click="activeProvider = 'hubtel'">
                                <input type="radio" name="settings[payment_provider]" value="hubtel" class="sr-only peer" {{ $setting->value === 'hubtel' ? 'checked' : '' }}>
                                <div class="border-2 rounded-lg p-4 text-center transition peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/20 border-gray-200 dark:border-gray-600 hover:border-orange-300">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">Hubtel</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mobile Money & Card</div>
                                    <div class="mt-2" x-show="activeProvider === 'hubtel'">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">Active</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative cursor-pointer" @click="activeProvider = 'paystack'">
                                <input type="radio" name="settings[payment_provider]" value="paystack" class="sr-only peer" {{ $setting->value === 'paystack' ? 'checked' : '' }}>
                                <div class="border-2 rounded-lg p-4 text-center transition peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 border-gray-200 dark:border-gray-600 hover:border-green-300">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">Paystack</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Card & Mobile Money</div>
                                    <div class="mt-2" x-show="activeProvider === 'paystack'">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @elseif($setting->type === 'boolean')
                        <select name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="1" {{ $setting->value ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ !$setting->value ? 'selected' : '' }}>Disabled</option>
                        </select>
                        @elseif($setting->type === 'integer' || $setting->type === 'float')
                        <input type="number" name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}"
                            value="{{ $setting->value }}" step="{{ $setting->type === 'float' ? '0.01' : '1' }}"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @elseif(str_contains($setting->key, 'secret') || str_contains($setting->key, 'password'))
                        <input type="password" name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}"
                            value="{{ $setting->value }}"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @elseif(strlen($setting->value ?? '') > 100)
                        <textarea name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">{{ $setting->value }}</textarea>
                        @else
                        <input type="text" name="settings[{{ $setting->key }}]" id="setting_{{ $setting->key }}"
                            value="{{ $setting->value }}"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @if($group === 'hubtel' || $group === 'paystack')
        </div>
        @endif

        @endforeach

        <div class="flex items-center justify-end">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
