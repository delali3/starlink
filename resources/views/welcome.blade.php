<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome - GhLinks Subscription</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-indigo-500 to-purple-600">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-6xl font-bold text-white mb-2">GhLinks</h1>
                <p class="text-indigo-100 text-lg">Daily & Monthly Subscription Platform</p>
            </div>

            <div class="bg-white rounded-lg shadow-2xl p-8">
                <div class="space-y-4">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Choose Your Plan</h2>
                        <p class="text-gray-600 mt-2">Flexible subscription options</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600">GHC 3</div>
                                <div class="text-sm text-green-700 mt-1">Daily</div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600">GHC 60</div>
                                <div class="text-sm text-blue-700 mt-1">Monthly</div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('login') }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center font-semibold py-3 px-4 rounded-lg transition duration-150">
                        User Login (OTP)
                    </a>

                    <a href="{{ route('admin.login') }}" class="block w-full bg-gray-600 hover:bg-gray-700 text-white text-center font-semibold py-3 px-4 rounded-lg transition duration-150">
                        Admin Login
                    </a>

                    <div class="text-center text-sm text-gray-500 mt-4">
                        <p>Secure payments via Paystack & Hubtel</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center text-white text-sm">
                <p>&copy; {{ date('Y') }} GhLinks. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
