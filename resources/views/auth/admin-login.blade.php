<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - GhLinks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-indigo-500 to-purple-600">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">GhLinks</h1>
                <p class="text-indigo-100">Admin Login</p>
            </div>

            <div class="bg-white rounded-lg shadow-2xl p-8">
                @if ($errors->any())
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" id="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-150">
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center space-y-2">
                    <div>
                        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                            Forgot your password?
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-800">
                            ← User Login (OTP)
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="/" class="text-white hover:text-indigo-100 text-sm">← Back to home</a>
            </div>
        </div>
    </div>
</body>
</html>
