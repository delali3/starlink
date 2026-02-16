<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - GhLinks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-indigo-500 to-purple-600">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">GhLinks</h1>
                <p class="text-indigo-100">Login to your account</p>
            </div>

            <div class="bg-white rounded-lg shadow-2xl p-8" x-data="{ otpSent: {{ session('success') ? 'true' : 'false' }}, savedPhone: '{{ old('phone') }}' }">
                @if (session('success'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
                @endif

                @if ($errors->any())
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <!-- Step 1: Phone Number -->
                <form method="POST" action="{{ route('login.send-otp') }}" x-show="!otpSent">
                    @csrf
                    <div class="mb-6">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" x-model="savedPhone" placeholder="0241234567" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required autofocus>
                        <p class="mt-1 text-xs text-gray-500">Enter your registered phone number</p>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-150">
                        Send OTP
                    </button>
                </form>

                <!-- Step 2: OTP Verification -->
                <form method="POST" action="{{ route('login.verify-otp') }}" x-show="otpSent" style="display: none;">
                    @csrf
                    <input type="hidden" name="phone" x-model="savedPhone">
                    
                    <div class="mb-4 text-center">
                        <p class="text-sm text-gray-600">OTP sent to: <span class="font-semibold" x-text="savedPhone"></span></p>
                    </div>
                    
                    @if (config('app.env') !== 'production')
                    <div class="mb-4 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                        <p class="text-xs text-blue-700">
                            <strong>Development Mode:</strong> Check storage/logs/laravel.log for the OTP code.
                        </p>
                    </div>
                    @endif
                    
                    <div class="mb-6">
                        <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">OTP Code</label>
                        <input type="text" id="otp" name="otp" placeholder="123456" maxlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl tracking-widest focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        <p class="mt-1 text-xs text-gray-500">Enter the 6-digit code sent to your phone</p>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-150">
                        Verify & Login
                    </button>

                    <button type="button" @click="otpSent = false" class="w-full mt-3 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        ← Change phone number
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('admin.login') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                        Admin Login
                    </a>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="/" class="text-white hover:text-indigo-100 text-sm">← Back to home</a>
            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
