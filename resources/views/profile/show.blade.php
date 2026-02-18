@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update your personal information</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                    <input type="text" id="phone" value="{{ $user->phone }}" disabled
                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-500">Phone number cannot be changed</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address (Optional)</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('email') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Used for payment confirmation emails</p>
                    @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Role:</span>
                            <span class="ml-1 font-medium text-gray-900 dark:text-white capitalize">{{ $user->getRoleNames()->first() ?? 'User' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Status:</span>
                            <span class="ml-1 font-medium {{ $user->status === 'active' ? 'text-green-600' : 'text-red-600' }} capitalize">{{ $user->status }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Member since:</span>
                            <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        @if($user->organization)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Organization:</span>
                            <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $user->organization->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                        Update Profile
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
