<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the dashboard based on user role.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('superadmin')) {
            return $this->superAdminDashboard();
        }

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        return $this->userDashboard();
    }

    /**
     * SuperAdmin Dashboard.
     */
    private function superAdminDashboard()
    {
        // Revenue statistics
        $revenueToday = Payment::where('status', 'success')
            ->whereDate('paid_at', today())
            ->sum('amount');

        $revenueThisMonth = Payment::where('status', 'success')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $revenueTotal = Payment::where('status', 'success')->sum('amount');

        // Subscription statistics
        $activeSubscriptions = Subscription::where('status', 'active')
            ->where('end_date', '>=', now())
            ->count();

        $expiredSubscriptions = Subscription::where('status', 'expired')
            ->orWhere('end_date', '<', now())
            ->count();

        // User statistics
        $totalUsers = User::role('user')->count();
        $activeUsers = User::role('user')->where('status', 'active')->count();
        $suspendedUsers = User::role('user')->where('status', 'suspended')->count();

        // Recent payments
        $recentPayments = Payment::with('user')
            ->where('status', 'success')
            ->latest()
            ->limit(10)
            ->get();

        // Monthly revenue chart data (last 12 months)
        $monthlyRevenue = Payment::where('status', 'success')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard.superadmin', compact(
            'revenueToday',
            'revenueThisMonth',
            'revenueTotal',
            'activeSubscriptions',
            'expiredSubscriptions',
            'totalUsers',
            'activeUsers',
            'suspendedUsers',
            'recentPayments',
            'monthlyRevenue'
        ));
    }

    /**
     * Admin Dashboard.
     */
    private function adminDashboard()
    {
        $totalUsers = User::role('user')->count();
        $activeUsers = User::role('user')->where('status', 'active')->count();

        // Users without active subscription
        $unpaidUsers = User::role('user')
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now());
            })
            ->count();

        // Recent payments
        $recentPayments = Payment::with('user')
            ->where('status', 'success')
            ->latest()
            ->limit(10)
            ->get();

        // Recent registrations
        $recentUsers = User::role('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.admin', compact(
            'totalUsers',
            'activeUsers',
            'unpaidUsers',
            'recentPayments',
            'recentUsers'
        ));
    }

    /**
     * User Dashboard.
     */
    private function userDashboard()
    {
        $user = auth()->user();

        // Get active subscription
        $activeSubscription = $user->activeSubscription()->first();

        // Get payment history
        $payments = $user->payments()
            ->latest()
            ->paginate(10);

        // Subscription status
        $subscriptionStatus = [
            'is_active' => $activeSubscription && $activeSubscription->isActive(),
            'type' => $activeSubscription?->type,
            'end_date' => $activeSubscription?->end_date,
            'days_remaining' => $activeSubscription?->daysRemaining() ?? 0,
        ];

        // Balance tracking
        $balanceInfo = [
            'total_paid' => $user->getTotalPaid(),
            'expected_amount' => $user->getExpectedAmount(),
            'balance' => $user->getBalance(),
            'amount_owed' => $user->getAmountOwed(),
            'days_remaining' => $user->getDaysRemaining(),
            'has_credit' => $user->hasCredit(),
            'credit_expiry_date' => $user->getCreditExpiryDate(),
        ];

        return view('dashboard.user', compact(
            'activeSubscription',
            'payments',
            'subscriptionStatus',
            'balanceInfo'
        ));
    }
}
