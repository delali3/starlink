@extends('layouts.app')

@section('title', 'Admins')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Management</h1>
            <p class="text-gray-600 mt-1">Manage system administrators</p>
        </div>
        @role('superadmin')
        <a href="{{ route('admins.create') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Create Admin
        </a>
        @endrole
    </div>

    <!-- Admins Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($admins->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        @role('superadmin')
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        @endrole
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($admins as $admin)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full {{ $admin->hasRole('superadmin') ? 'bg-purple-100' : 'bg-indigo-100' }} flex items-center justify-center {{ $admin->hasRole('superadmin') ? 'text-purple-600' : 'text-indigo-600' }} font-semibold">
                                    {{ substr($admin->name, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $admin->name }}</div>
                                    @if($admin->id === auth()->id())
                                    <span class="text-xs text-gray-500">(You)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $admin->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $admin->phone }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($admin->hasRole('superadmin'))
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                SuperAdmin
                            </span>
                            @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                Admin
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $admin->created_at->format('M d, Y') }}
                        </td>
                        @role('superadmin')
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($admin->id !== auth()->id())
                            <form action="{{ route('admins.destroy', $admin) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this admin?')">
                                    Delete
                                </button>
                            </form>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        @endrole
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $admins->links() }}
        </div>
        @else
        <p class="text-gray-500 text-center py-12">No admins found</p>
        @endif
    </div>
</div>
@endsection
