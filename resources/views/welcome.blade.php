<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome - GhLinks Subscription</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-5xl w-full">
            <!-- Logo and Title -->
            <div class="text-center mb-12">
                <div class="inline-block mb-4">
                    <div class="h-20 w-20 bg-white rounded-full flex items-center justify-center mx-auto shadow-lg">
                        <span class="text-3xl font-bold text-indigo-600">GH</span>
                    </div>
                </div>
                <h1 class="text-5xl sm:text-6xl font-bold text-white mb-3 tracking-tight">GhLinks</h1>
                <p class="text-xl sm:text-2xl text-indigo-100 font-light">Flexible Subscription Platform</p>
                <p class="text-indigo-200 mt-2 text-sm sm:text-base">Pay-as-you-go with complete control over your subscription</p>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <div class="grid md:grid-cols-2 gap-0">
                    <!-- Features Section -->
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 p-8 sm:p-10">
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Why Choose Us?</h2>
                        
                        <div class="space-y-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-800">Flexible Payments</h3>
                                    <p class="text-gray-600 text-sm mt-1">Pay any amount you want. Choose daily, monthly, or custom payment options.</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-800">Track Your Balance</h3>
                                    <p class="text-gray-600 text-sm mt-1">Real-time balance tracking. Know exactly how many days you have left.</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 bg-pink-600 rounded-lg flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-800">Secure Payments</h3>
                                    <p class="text-gray-600 text-sm mt-1">Multiple payment options via Hubtel and Paystack. Fast and secure.</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 bg-green-600 rounded-lg flex items-center justify-center">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-800">No Weekend Charges</h3>
                                    <p class="text-gray-600 text-sm mt-1">Save money! Only weekdays count toward your subscription.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Login Section -->
                    <div class="p-8 sm:p-10 flex flex-col justify-center">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Get Started</h2>
                            <p class="text-gray-600">Login to manage your subscription</p>
                        </div>

                        <div class="space-y-4">
                            <a href="{{ route('login') }}" class="group relative block w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center font-semibold py-4 px-6 rounded-xl transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <div class="flex items-center justify-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    User Login (OTP)
                                </div>
                            </a>

                            <a href="{{ route('admin.login') }}" class="group relative block w-full bg-gray-700 hover:bg-gray-800 text-white text-center font-semibold py-4 px-6 rounded-xl transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <div class="flex items-center justify-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Admin Login
                                </div>
                            </a>

                            <div class="pt-6 mt-6 border-t border-gray-200">
                                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-700 font-medium">✨ Pay only for the days you use</p>
                                    <p class="text-xs text-gray-500 mt-1">Complete transparency with real-time balance tracking</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-indigo-100 text-sm">&copy; {{ date('Y') }} GhLinks. All rights reserved.</p>
                <p class="text-indigo-200 text-xs mt-1">Secure. Flexible. Transparent.</p>
                <p class="text-indigo-300 text-xs mt-2">Powered by <a href="https://ghprofit.com" target="_blank" rel="noopener noreferrer" class="font-semibold hover:text-white transition">GhProfit</a></p>
            </div>
        </div>
    </div>

    <!-- Alpine.js for any interactive features -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
