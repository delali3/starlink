@extends('layouts.app')

@section('title', 'SuperAdmin Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">SuperAdmin Dashboard</h1>
        <p class="text-gray-600 mt-1">Complete system overview and analytics</p>
    </div>

    <!-- Revenue Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Revenue Today</p>
                    <p class="mt-2 text-3xl font-bold">GHC {{ number_format($revenueToday, 2) }}</p>
                    <p class="text-green-100 text-xs mt-1">Base: {{ number_format($baseRevenueToday, 2) }} + Fee: {{ number_format($serviceChargeToday, 2) }}</p>
                </div>
                <div class="bg-green-400 bg-opacity-30 rounded-full p-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Revenue This Month</p>
                    <p class="mt-2 text-3xl font-bold">GHC {{ number_format($revenueThisMonth, 2) }}</p>
                    <p class="text-blue-100 text-xs mt-1">Base: {{ number_format($baseRevenueThisMonth, 2) }} + Fee: {{ number_format($serviceChargeThisMonth, 2) }}</p>
                </div>
                <div class="bg-blue-400 bg-opacity-30 rounded-full p-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Revenue</p>
                    <p class="mt-2 text-3xl font-bold">GHC {{ number_format($revenueTotal, 2) }}</p>
                    <p class="text-purple-100 text-xs mt-1">Base: {{ number_format($baseRevenueTotal, 2) }} + Fee: {{ number_format($serviceChargeTotal, 2) }}</p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 rounded-full p-3">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Active Subscriptions</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($activeSubscriptions) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Expired Subscriptions</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($expiredSubscriptions) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Users</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totalUsers) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Active Users</p>
            <p class="mt-1 text-2xl font-bold text-green-600">{{ number_format($activeUsers) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Suspended Users</p>
            <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format($suspendedUsers) }}</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Revenue</h2>
            <canvas id="revenueChart" height="250"></canvas>
        </div>

        <!-- User Growth Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">User Growth</h2>
            <canvas id="userGrowthChart" height="250"></canvas>
        </div>
    </div>

    @if(isset($organizations) && $organizations->count() > 0)
    <!-- Organization Stats -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Organizations Overview</h2>
            <a href="{{ route('organizations.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($organizations as $org)
            <a href="{{ route('organizations.show', $org) }}" class="block p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-indigo-400 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $org->name }}</h3>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $org->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($org->status) }}
                    </span>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Users:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $org->users_count ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Revenue:</span>
                        <span class="text-sm font-bold text-green-600">GHC {{ number_format($org->total_revenue ?? 0, 2) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Payments -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Recent Successful Payments</h2>
                <a href="{{ route('payments.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View all →</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            @if($recentPayments->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentPayments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $payment->user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $payment->user->phone }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-green-600">GHC {{ number_format($payment->amount, 2) }}</div>
                            @if($payment->service_charge > 0)
                            <div class="text-xs text-gray-500">Base: {{ number_format($payment->base_amount, 2) }} + Fee: {{ number_format($payment->service_charge, 2) }}</div>
                            @else
                            <div class="text-xs text-gray-500">No service charge</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $providerStyle = match($payment->payment_provider) {
                                    'paystack' => 'bg-blue-100 text-blue-800',
                                    'cash' => 'bg-green-100 text-green-800',
                                    default => 'bg-orange-100 text-orange-800',
                                };
                            @endphp
                            <span class="text-xs font-medium px-2 py-1 rounded {{ $providerStyle }}">
                                {{ ucfirst($payment->payment_provider) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $payment->paid_at?->format('M d, Y g:i A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
                            {{ Str::limit($payment->reference, 20) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-gray-500 text-center py-8">No payments yet</p>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admins.create') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition">
                Register Admin
            </a>
            <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                Register User
            </a>
            <a href="{{ route('organizations.create') }}" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg transition">
                Create Organization
            </a>
            <a href="{{ route('sms.create') }}" class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition">
                Send Bulk SMS
            </a>
            <a href="{{ route('users.unpaid') }}" class="inline-flex items-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-lg transition">
                View Unpaid Users
            </a>
            <a href="{{ route('export.payments') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                Export Payments CSV
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))) !!},
            datasets: [{
                label: 'Revenue (GHC)',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    @if(isset($monthlyUsers))
    const userCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyUsers->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))) !!},
            datasets: [{
                label: 'New Users',
                data: {!! json_encode($monthlyUsers->pluck('total')) !!},
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    @endif
</script>
@endpush
@endsection
