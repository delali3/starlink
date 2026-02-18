@extends('layouts.app')

@section('title', 'Edit Organization')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('organizations.show', $organization) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">&larr; Back to {{ $organization->name }}</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Edit Organization</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('organizations.update', $organization) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Organization Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $organization->name) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="domain" class="block text-sm font-medium text-gray-700">Domain (Optional)</label>
                <input type="text" name="domain" id="domain" value="{{ old('domain', $organization->domain) }}" placeholder="example.com"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('domain')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4">
                <a href="{{ route('organizations.show', $organization) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md text-sm transition">Update Organization</button>
            </div>
        </form>
    </div>
</div>
@endsection
